<?php
namespace Giant\SheetSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;

class GoogleSheet extends AbstractHelper
{
    const SPREADSHEET_ID = '1b8cS_9ERLh_aJ7jdw-VD7IWIyrf_Ia-gizgIbvE-mzg';
    const READ_SHEET = 'Hoja 1';
    const WRITE_SHEET = 'SKUs no encontrados';

    protected $curl;

    public function __construct(Context $context, Curl $curl)
    {
        parent::__construct($context);
        $this->curl = $curl;
    }

    public function getAccessToken()
    {
        $hostname = getenv('REPLIT_CONNECTORS_HOSTNAME');
        $replIdentity = getenv('REPL_IDENTITY');
        $webReplRenewal = getenv('WEB_REPL_RENEWAL');

        if ($replIdentity) {
            $xReplitToken = 'repl ' . $replIdentity;
        } elseif ($webReplRenewal) {
            $xReplitToken = 'depl ' . $webReplRenewal;
        } else {
            throw new \Exception('No se encontró token de autenticación de Replit');
        }

        $url = 'https://' . $hostname . '/api/v2/connection?include_secrets=true&connector_names=google-sheet';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Replit-Token: ' . $xReplitToken
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Error al obtener token de Google Sheets (HTTP ' . $httpCode . ')');
        }

        $data = json_decode($response, true);
        $connection = $data['items'][0] ?? null;

        if (!$connection) {
            throw new \Exception('Google Sheets no está conectado. Configure la integración primero.');
        }

        $accessToken = $connection['settings']['access_token']
            ?? $connection['settings']['oauth']['credentials']['access_token']
            ?? null;

        if (!$accessToken) {
            throw new \Exception('No se encontró access_token para Google Sheets');
        }

        return $accessToken;
    }

    public function readSheet()
    {
        $token = $this->getAccessToken();
        $range = urlencode(self::READ_SHEET . '!A1:F10000');
        $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . self::SPREADSHEET_ID . '/values/' . $range;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 60
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Error al leer Google Sheet (HTTP ' . $httpCode . '): ' . $response);
        }

        $data = json_decode($response, true);
        return $data['values'] ?? [];
    }

    public function writeNotFoundSkus(array $skus)
    {
        if (empty($skus)) {
            return true;
        }

        $token = $this->getAccessToken();

        $clearUrl = 'https://sheets.googleapis.com/v4/spreadsheets/' . self::SPREADSHEET_ID
            . '/values/' . urlencode(self::WRITE_SHEET . '!A:F') . ':clear';

        $ch = curl_init($clearUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        curl_exec($ch);
        curl_close($ch);

        $range = urlencode(self::WRITE_SHEET . '!A1');
        $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . self::SPREADSHEET_ID
            . '/values/' . $range . '?valueInputOption=RAW';

        $values = [['SKU', 'Precio', 'Precio Descuento', 'Cantidad', 'Fecha Inicio', 'Fecha Fin', 'Fecha Reporte']];
        foreach ($skus as $sku) {
            $values[] = [
                $sku['sku'],
                $sku['price'] ?? '',
                $sku['special_price'] ?? '',
                $sku['qty'] ?? '',
                $sku['from_date'] ?? '',
                $sku['to_date'] ?? '',
                date('Y-m-d H:i:s')
            ];
        }

        $payload = json_encode(['range' => self::WRITE_SHEET . '!A1', 'majorDimension' => 'ROWS', 'values' => $values]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 60
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Error al escribir SKUs no encontrados (HTTP ' . $httpCode . '): ' . $response);
        }

        return true;
    }
}
