<?php

namespace Olegnax\InstagramMin\Model\Token;

use Exception;

class InstagramAPI
{
    const URL_BASE_API = "https://api.instagram.com/";
    const URL_BASE_GRAPH = "https://graph.instagram.com/";

    const URL_PATH_AUTHORIZE = "oauth/authorize";
    const URL_PATH_ACCESSTOKEN = "oauth/access_token";
    /**
     * @var int
     */
    public $userId;
    /**
     * @var string
     */
    public $redirectUri;
    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @param array $options
     * @param string $method
     *
     * @return bool|string
     */
    private function curl(
        $options,
        $method = "GET",
        $to = "string"
    ) {
        $default = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_TIMEOUT => 0,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];
        $options = array_replace($default, $options);

        $options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        switch ($options[CURLOPT_CUSTOMREQUEST]) {
            case "POST":
                break;
            case "GET":
            default:
                $options[CURLOPT_CUSTOMREQUEST] = "GET";
                if (isset($options[CURLOPT_POSTFIELDS])) {
                    if (is_array($options[CURLOPT_POSTFIELDS])) {
                        $options[CURLOPT_POSTFIELDS] = http_build_query($options[CURLOPT_POSTFIELDS]);
                    }
                    $options[CURLOPT_URL] .= "?" . (string)$options[CURLOPT_POSTFIELDS];
                }
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $data = curl_exec($ch);
        curl_close($ch);

        switch ($to) {
            case "array":
                if (!empty($data)) {
                    $data = json_decode((string)$data, true);
                }
                break;
            case "object":
                if (!empty($data)) {
                    $data = json_decode((string)$data);
                }
        }

        return $data;
    }

    public function setUserId($userId)
    {
        if (0 < (int)$userId) {
            $this->userId = (int)$userId;
        }

        return $this;
    }

    public function setToken($accesToken)
    {
        if (!empty($accesToken)) {
            $this->accessToken = $accesToken;
        }

        return $this;
    }

    public function getUser($userId = null, $fields = [])
    {
        if (empty($userId)) {
            $userId = "me";
        }

        $data = $this->curl([
            CURLOPT_URL => static::URL_BASE_GRAPH . $userId,
            CURLOPT_POSTFIELDS => array_filter([
                "fields" => implode(",", $fields),
                "access_token" => $this->accessToken,
            ]),
        ], "get", "array");

        if (isset($data["error"])) {
            throw new Exception($data["error"]["message"], isset($data["error"]["code"]) ? $data["error"]["code"] : 500);
        }

        return $data;
    }

    public function getUserMedia($userId = null, $fields = [], $limit = 25, $after = "")
    {
        if (empty($userId)) {
            $userId = $this->userId;
        }
        if(!isset($this->accessToken)){
            throw new Exception(__('You need to generate token first in Olegnax / Instagram / Configuration'));
        }
        $data = $this->curl([
            CURLOPT_URL => static::URL_BASE_GRAPH . $userId . '/media',
            CURLOPT_POSTFIELDS => array_filter([
                "fields" => implode(",", $fields),
                "access_token" => $this->accessToken,
                "pretty" => 1,
                "limit" => $limit,
                "after" => $after,
            ]),
        ], "get", "array");

        if (isset($data["error"])) {
            throw new Exception($data["error"]["message"], isset($data["error"]["code"]) ? $data["error"]["code"] : 500);
        }

        return $data;
    }

    public function getMedia($mediaId = null, $fields = [])
    {
        $data = $this->curl([
            CURLOPT_URL => static::URL_BASE_GRAPH . $mediaId,
            CURLOPT_POSTFIELDS => array_filter([
                "fields" => implode(",", $fields),
                "access_token" => $this->accessToken,
            ]),
        ], "get", "array");

        if (isset($data["error"])) {
            throw new Exception($data["error"]["message"], isset($data["error"]["code"]) ? $data["error"]["code"] : 500);
        }

        return $data;
    }

}