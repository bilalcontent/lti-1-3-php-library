<?php
namespace IMSGlobal\LTI;

class LTI_Platform_Notification_Service {

    private $service_connector;
    private $service_data;

    public function __construct(LTI_Service_Connector $service_connector, $service_data) {
        $this->service_connector = $service_connector;
        $this->service_data = $service_data;
    }

    public function register_handler() {
        if (!in_array("https://purl.imsglobal.org/spec/lti/scope/noticehandlers", $this->service_data['scope'])) {
            throw new LTI_Exception('Missing required scope', 1);
        }

        $url = $this->service_data['platform_notification_service_url'];

        $body = json_encode([
            'notice_type' => 'LtiAssetProcessorSubmissionNotice',
            'handler_url' => route('asset-processor-pns'),
        ]);

        clock('register_handler Log', $body);
        clock('register_handler ', ['$url' => $url, 'urldecode' => urldecode($url), 'scope' => $this->service_data['scope']]);

        return $this->service_connector->make_service_request(
            $this->service_data['scope'],
            'POST',
            $url,
            strval($body),
            'application/vnd.ims.lis.v1.score+json'
        );
    }
}
?>