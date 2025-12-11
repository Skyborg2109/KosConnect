<?php
include '../config/qris_generator.php';
// The generator outputs directly, so capturing it might be tricky if it uses echo.
// But the class returns the string in generateQRIS(). 
// Wait, the file 'qris_generator.php' has side effects:
// header('Content-Type: text/plain; charset=utf-8');
// $generator = new QRISGenerator();
// echo $generator->generateQRIS();
// exit();

// So including it will immediately output and exit.
// I will create a modified version of the class here to test.

class QRISDebug {
    private $merchantAccountNumber = '0232010926735090'; // Dummy ID from original
    private $merchantName = 'WILLHELMUS JUANESS PATUDANG'; // Trimmed to 19 chars? 25 limit.
    private $merchantCity = 'SURABAYA';
    private $merchantCountryCode = 'ID';
    private $transactionCurrency = '360'; 
    private $merchantCategoryCode = '5411';
    private $referenceLabel = 'KosConnect';

    public function generateQRIS() {
        $qris = '';
        $qris .= '0001' . '01'; // Payload Format
        $qris .= '0101' . '12'; // POI Method (12 = Dynamic, 11 = Static)
        
        $merchantInfo = '';
        $merchantInfo .= '00' . '15' . 'id.co.bri.brimo'; 
        $merchantInfo .= '03' . '16' . $this->merchantAccountNumber; 
        $qris .= '26' . str_pad(strlen($merchantInfo), 2, '0', STR_PAD_LEFT) . $merchantInfo;
        
        $qris .= '52' . '04' . $this->merchantCategoryCode;
        $qris .= '53' . '03' . $this->transactionCurrency;
        
        // Tag 54 (Amount) is missing! If dynamic, it might be okay, but for static it's 11.
        // User didn't specify amount in generator loop.
        
        $qris .= '58' . '02' . $this->merchantCountryCode;
        
        $merchantNameTrimmed = substr($this->merchantName, 0, 25);
        $qris .= '59' . str_pad(strlen($merchantNameTrimmed), 2, '0', STR_PAD_LEFT) . $merchantNameTrimmed;
        
        $merchantCityTrimmed = substr($this->merchantCity, 0, 15);
        $qris .= '60' . str_pad(strlen($merchantCityTrimmed), 2, '0', STR_PAD_LEFT) . $merchantCityTrimmed;
        
        $qris .= '05' . str_pad(strlen($this->referenceLabel), 2, '0', STR_PAD_LEFT) . $this->referenceLabel;
        
        $crcInput = $qris . '6304';
        $crc = $this->calculateCRC16($crcInput);
        
        return $crcInput . $crc;
    }

    public function calculateCRC16($data) {
        $crc = 0xFFFF; // Initial value
        $polynomial = 0x1021; // CRC-CCITT (XModem)
        
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000)) {
                    $crc = (($crc << 1) ^ $polynomial);
                } else {
                    $crc = ($crc << 1);
                }
            }
            $crc &= 0xFFFF; // Limit to 16 bits
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}

$debug = new QRISDebug();
echo "Generated QRIS String:\n";
echo $debug->generateQRIS();
?>
