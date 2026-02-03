<?php
namespace IMSGlobal\LTI;

class LTI_Deep_Link_Resource {

    private $type = 'ltiResourceLink';
    private $title;
    private $text;
    private $url;
    private $report_url;
    private $lineitem;
    private $custom_params = [];
    private $target = 'iframe';

    public static function new() {
        return new LTI_Deep_Link_Resource();
    }

    public function get_type() {
        return $this->type;
    }

    public function set_type($value) {
        $this->type = $value;
        return $this;
    }

    public function get_title() {
        return $this->title;
    }

    public function set_title($value) {
        $this->title = $value;
        return $this;
    }

    public function get_text() {
        return $this->text;
    }

    public function set_text($value) {
        $this->text = $value;
        return $this;
    }

    public function get_url() {
        return $this->url;
    }

    public function set_url($value) {
        $this->url = $value;
        return $this;
    }

    public function get_report_url() {
        return $this->report_url;
    }

    public function set_report_url($value) {
        $this->report_url = $value;
        return $this;
    }

    public function get_lineitem() {
        return $this->lineitem;
    }

    public function set_lineitem($value) {
        $this->lineitem = $value;
        return $this;
    }

    public function get_custom_params() {
        return $this->custom_params;
    }

    public function set_custom_params($value) {
        $this->custom_params = $value;
        return $this;
    }

    public function get_target() {
        return $this->target;
    }

    public function set_target($value) {
        $this->target = $value;
        return $this;
    }

    public function to_array() {
        $resource = [
            "type" => $this->type,
            "title" => $this->title,
            "text" => $this->text,
            "url" => $this->url,
            "presentation" => [
                "documentTarget" => $this->target,
            ],
            "custom" => $this->custom_params,
        ];
        if ($this->lineitem !== null) {
            $resource["lineItem"] = [
                "scoreMaximum" => $this->lineitem->get_score_maximum(),
                "label" => $this->lineitem->get_label(),
            ];
        }
        if($this->type == "ltiAssetProcessor") {
            $resource["report"] = [
                "supportedTypes" => ['originality'],
                "url" => $this->get_report_url(),
                "released" => true,
                "indicator" => true,
            ];
        }
        return $resource;
    }
}
?>