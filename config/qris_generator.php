<?php
// Prevent any output except QRIS string
ob_clean();

/**
 * QRIS Generator - Menghasilkan QRIS String yang valid sesuai standar BI
 */
class QRISGenerator {
    private $merchantAccountNumber = '0232010926735090';
    private $merchantName = 'WILLHELMUS JUANESS PATUDANG';
    private $merchantCity = 'SURABAYA';
    private $merchantCountryCode = 'ID';
    private $transactionCurrency = '360'; // IDR
    private $merchantCategoryCode = '5411';
    private $referenceLabel = 'KosConnect';

    public function generateQRIS($amount = 0) {
        // Buat struktur QRIS yang benar
        $qris = '';
        
        // Format Indicator (Tag 00)
        $qris .= '0001' . '01';
        
        // Point of Initiation Method (Tag 01)
        // 11 = Static, 12 = Dynamic
        // Jika ada amount, gunakan 12 (Dynamic), jika tidak 11 (Static)
        $method = ($amount > 0) ? '12' : '11';
        $qris .= '0101' . $method;
        
        // Merchant Account Information (Tag 26)
        $merchantInfo = '';
        $merchantInfo .= '00' . '15' . 'id.co.bri.brimo'; // Globally Unique ID
        $merchantInfo .= '03' . '16' . $this->merchantAccountNumber; // Account Number (16 digits)
        $qris .= '26' . str_pad(strlen($merchantInfo), 2, '0', STR_PAD_LEFT) . $merchantInfo;
        
        // Merchant Category Code (Tag 52)
        $qris .= '52' . '04' . $this->merchantCategoryCode;
        
        // Transaction Currency (Tag 53)
        $qris .= '53' . '03' . $this->transactionCurrency;
        
        // Transaction Amount (Tag 54) - OPTIONAL for Static, MANDATORY for Dynamic if used
        if ($amount > 0) {
            $amountStr = (string)$amount;
            $qris .= '54' . str_pad(strlen($amountStr), 2, '0', STR_PAD_LEFT) . $amountStr;
        }
        
        // Country Code (Tag 58)
        $qris .= '58' . '02' . $this->merchantCountryCode;
        
        // Merchant Name (Tag 59)
        $merchantNameTrimmed = substr($this->merchantName, 0, 25);
        $qris .= '59' . str_pad(strlen($merchantNameTrimmed), 2, '0', STR_PAD_LEFT) . $merchantNameTrimmed;
        
        // Merchant City (Tag 60)
        $merchantCityTrimmed = substr($this->merchantCity, 0, 15);
        $qris .= '60' . str_pad(strlen($merchantCityTrimmed), 2, '0', STR_PAD_LEFT) . $merchantCityTrimmed;
        
        // Reference Label (Tag 05)
        $qris .= '05' . str_pad(strlen($this->referenceLabel), 2, '0', STR_PAD_LEFT) . $this->referenceLabel;
        
        // Calculate CRC (Tag 63)
        $crcInput = $qris . '6304';
        $crc = $this->calculateCRC16($crcInput);
        $qris .= '63' . '04' . $crc;
        
        return $qris;
    }

    private function calculateCRC16($str) {
        $crc = 0xFFFF;
        for ($c = 0; $c < strlen($str); $c++) {
            $crc ^= ord($str[$c]) << 8;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }
        $hex = strtoupper(dechex($crc & 0xFFFF));
        return str_pad($hex, 4, '0', STR_PAD_LEFT);
    }
}

// Generate dan output QRIS
header('Content-Type: text/plain; charset=utf-8');
$generator = new QRISGenerator();
$amount = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;
echo $generator->generateQRIS($amount);
exit();
?>
