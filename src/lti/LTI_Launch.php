<?php
namespace IMSGlobal\LTI;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

JWT::$leeway = 5;

class LTI_Launch {

    private $db;
    private $cache;
    private $request;
    private $cookie;
    private $jwt;
    private $registration;
    private $launch_id;

    /**
     * Constructor
     *
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     * @param Cookie    $cookie     Instance of the Cookie interface used to set and read cookies. Will default to using $_COOKIE and setcookie.
     */
    function __construct(Database $database) {
        $this->db = $database;

        $this->launch_id = md5(uniqid("_", true));

    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new(Database $database ) {
        return new LTI_Launch($database);
    }


    /**
     * Validates all aspects of an incoming LTI message launch and caches the launch if successful.
     *
     * @param array|string  $request    An array of post request parameters. If not set will default to $_POST.
     *
     * @throws LTI_Exception        Will throw an LTI_Exception if validation fails.
     * @return LTI_Launch   Will return $this if validation is successful.
     */
    public function validate(array $request = null, $jwt = null) {

        if ($request === null) {
            $request = $_POST;
        }
        $this->request = $request;

        return $this->validate_jwt_format($jwt)
            ->validate_registration()
            ->validate_jwt_signature($jwt)
            ->validate_deployment();
    }

    /**
     * Returns whether or not the current launch can use the Platform Notification service.
     *
     * @return boolean  Returns a boolean indicating the availability of assignments and grades.
     */
    public function has_pns($service_name = 'platformnotificationservice') {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/'.$service_name]);
    }

    /**
     * Fetches an instance of the platform notification service for the current launch.
     *
     * @return LTI_Platform_Notification_Service An instance of the Platform notification service that can be used to make calls within the scope of the current launch.
     */
    public function get_pns($service_data = 'https://purl.imsglobal.org/spec/lti/claim/platformnotificationservice') {
        return new LTI_Platform_Notification_Service(
            new LTI_Service_Connector($this->registration),
            $this->jwt['body'][$service_data]);
    }
    /**
     * Fetches an instance of the  service connector for the current launch.
     *
     * @return LTI_Service_Connector An instance of the  service connector that can be used to make calls within the scope of the current launch.
     */
    public function get_service_connector() {
        return new LTI_Service_Connector($this->registration);
    }

    /**
     * Get Registration for the current launch.
     *
     */
    public function get_registration() {
        return $this->registration;
    }


    /**
     * Fetches the decoded body of the JWT used in the current launch.
     *
     * @return array|object Returns the decoded json body of the launch as an array.
     */
    public function get_launch_data() {
        return $this->jwt['body'];
    }

    /**
     * Get the unique launch id for the current launch.
     *
     * @return string   A unique identifier used to re-reference the current launch in subsequent requests.
     */
    public function get_launch_id() {
        return $this->launch_id;
    }

    private function parseJwk(array $jwk, string $defaultAlg = 'RS256')
	{
    	if (!isset($jwk['alg'])) {
        	$jwk['alg'] = $defaultAlg;
    	}

    	return JWK::parseKey($jwk);
	}

    private function get_public_key() {
        $key_set_url = $this->registration->get_key_set_url();

        // Download key set
        $public_key_set = json_decode(file_get_contents($key_set_url), true);

        if (empty($public_key_set)) {
            // Failed to fetch public keyset from URL.
            throw new LTI_Exception("Failed to fetch public key", 1);
        }

        // Find key used to sign the JWT (matches the KID in the header)
        foreach ($public_key_set['keys'] as $key) {
            if ($key['kid'] == $this->jwt['header']['kid']) {
                try {
                  $parsed_key = $this->parseJwk($key);
                  return openssl_pkey_get_details($parsed_key->getKeyMaterial());
                } catch(\Exception $e) {
                    return false;
                }
            }
        }

        // Could not find public key with a matching kid and alg.
        throw new LTI_Exception("Unable to find public key", 1);
    }


    private function validate_jwt_format($jwt) {
        if (empty($jwt)) {
            throw new LTI_Exception("Missing JWT", 1);
        }

        // Get parts of JWT.
        $jwt_parts = explode('.', $jwt);

        if (count($jwt_parts) !== 3) {
            // Invalid number of parts in JWT.
            throw new LTI_Exception("Invalid id_token, JWT must contain 3 parts", 1);
        }

        // Decode JWT headers.
        $this->jwt['header'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]), true);
        // Decode JWT Body.
        $this->jwt['body'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]), true);

        return $this;
    }

    private function validate_registration() {
        // Find registration.
        $this->registration = $this->db->find_registration_by_issuer_and_client_id($this->jwt['body']['iss'], $this->jwt['body']['aud']);

        if (empty($this->registration)) {
            throw new LTI_Exception("Registration not found.", 1);
        }

        // Check client id.
        $client_id = is_array($this->jwt['body']['aud']) ? $this->jwt['body']['aud'][0] : $this->jwt['body']['aud'];
        if ( $client_id !== $this->registration->get_client_id()) {
            // Client not registered.
            throw new LTI_Exception("Client id not registered for this issuer", 1);
        }

        return $this;
    }

    private function validate_jwt_signature($jwt) {
        // Fetch public key.
        $public_key = $this->get_public_key();

    
        // Validate JWT signature
        try {
            JWT::decode($jwt, new Key($public_key['key'], 'RS256'));
            // JWT::decode($jwt, $public_key['key'], 'RS256');
        } catch(\Exception $e) {
            // Error validating signature.
            throw new LTI_Exception("Invalid signature on id_token", 1);
        }

        return $this;
    }

    private function validate_deployment() {
        // Find deployment.
        $deployment = $this->db->find_deployment($this->jwt['body']['iss'], $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id']);

        if (empty($deployment)) {
            // deployment not recognized.
            throw new LTI_Exception("Unable to find deployment", 1);
        }

        return $this;
    }

}
?>