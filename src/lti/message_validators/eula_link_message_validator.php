<?php
namespace IMSGlobal\LTI;

class Eula_Link_Message_Validator implements Message_Validator {
    public function can_validate($jwt_body) {
        return $jwt_body['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiEulaRequest';
    }

    public function validate($jwt_body) {
        if (empty($jwt_body['sub'])) {
            throw new LTI_Exception('Must have a user (sub)');
        }
        if ($jwt_body['https://purl.imsglobal.org/spec/lti/claim/version'] !== '1.3.0') {
            throw new LTI_Exception('Incorrect version, expected 1.3.0');
        }
        if (!isset($jwt_body['https://purl.imsglobal.org/spec/lti/claim/roles'])) {
            throw new LTI_Exception('Missing Roles Claim');
        }
        if (empty($jwt_body['https://purl.imsglobal.org/spec/lti/claim/eulaservice'])) {
            throw new LTI_Exception('Missing Eula Service');
        }

        if (!isset($jwt_body['https://purl.imsglobal.org/spec/lti/claim/eulaservice']['url'])) {
            throw new LTI_Exception('Missing Eula Service URL');
        }

        if (
            !isset($jwt_body['https://purl.imsglobal.org/spec/lti/claim/eulaservice']['scope']) ||
            ! in_array('https://purl.imsglobal.org/spec/lti/scope/eula/user', $jwt_body['https://purl.imsglobal.org/spec/lti/claim/eulaservice']['scope'])
        ) {
            throw new LTI_Exception('Missing Required scope for Eula');
        }


        return true;
    }
}
?>