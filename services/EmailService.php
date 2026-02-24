<?php

class EmailService {
    private static $apiKey = null;

    private static function init() {
        if (self::$apiKey === null) {
            // Priority: Environment Variable -> Hardcoded fallback (not recommended)
            self::$apiKey = getenv('RESEND_API_KEY') ?: ''; 
        }
    }

    /**
     * Send email using Resend.com API (CURL)
     */
    public static function send($to, $subject, $htmlContent, $fullname = '') {
        self::init();
        
        if (empty(self::$apiKey)) {
            error_log("Resend API Key is missing.");
            return false;
        }

        $url = 'https://api.resend.com/emails';
        
        $data = [
            'from' => 'KosConnect <onboarding@resend.dev>', // Default Resend test email
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlContent,
        ];

        // If user has a verified domain, they should update the 'from' address
        $customFrom = getenv('RESEND_FROM_EMAIL');
        if ($customFrom) {
            $data['from'] = $customFrom;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("Resend API Error: " . $response);
            return false;
        }
    }
}
