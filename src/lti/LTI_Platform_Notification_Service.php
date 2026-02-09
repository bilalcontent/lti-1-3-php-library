<?php
namespace IMSGlobal\LTI;
use Illuminate\Support\Facades\Log;

class LTI_Platform_Notification_Service {

    private $service_connector;
    private $service_data;

    public function __construct(LTI_Service_Connector $service_connector, $service_data) {
        $this->service_connector = $service_connector;
        $this->service_data = $service_data;
    }

    public function register_handler($handler_url) {
        if (!in_array("https://purl.imsglobal.org/spec/lti/scope/noticehandlers", $this->service_data['scope'])) {
            throw new LTI_Exception('Missing required scope', 1);
        }

        $url = $this->service_data['platform_notification_service_url'];

        $body = json_encode([
            'notice_type' => 'LtiAssetProcessorSubmissionNotice',
            'handler' => $handler_url,
        ]);


        return $this->service_connector->make_service_request(
            $this->service_data['scope'],
            'PUT',
            $url,
            strval($body),
            'application/vnd.ims.lis.v1.score+json'
        );
    }

    public function get_d2l_assets($asset_url) {
        if (!in_array("https://purl.imsglobal.org/spec/lti/scope/asset.readonly", $this->service_data['scope'])) {
            throw new LTI_Exception('Missing required scope', 1);
        }

        return $this->service_connector->make_service_request(
            $this->service_data['scope'],
            'GET',
            $asset_url,
            null,
            null,
            '*/*'
        );
    }

    public function send_d2l_asset_processor_report($asset_report_payload) {
        if (!in_array("https://purl.imsglobal.org/spec/lti/scope/report", $this->service_data['scope'])) {
            throw new LTI_Exception('Missing required scope', 1);
        }

        $report_url = $this->service_data['report_url'];

        $body = json_encode($asset_report_payload);

        try {
            $response = $this->service_connector->make_service_request(
                $this->service_data['scope'],
                'POST',
                $report_url,
                strval($body),
                'application/vnd.ims.lis.v1.score+json'
            );
        } catch (\Throwable $throwable) {
            Log::info('send_d2l_asset_processor_report error handling');
            Log::info($throwable->getMessage());
            return false;
        }

        return $response;
    }


}
?>