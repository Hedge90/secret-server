<?php

class ResponseHandler {
    public static function send($data, $statusCode = 200, $format = 'json') {
        http_response_code($statusCode);

        $format = strtolower($format);
        switch ($format) {
            case 'xml':
                header('Content-Type: application/xml');
                echo self::toXML($data);
                break;
            case 'json':
            default:
                header('Content-Type: application/json');
                echo json_encode($data, JSON_PRETTY_PRINT);
                break;
        }
    }

    private static function toXML($data, $rootElement = 'response', $xml = null) {
        if ($xml === null) {
            $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><$rootElement></$rootElement>");
        }

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                self::toXML($value, $key, $xml->addChild($key));
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }

        return $xml->asXML();
    }
}


?>