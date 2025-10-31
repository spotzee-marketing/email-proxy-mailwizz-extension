<?php

declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Spotzee Email Proxy API Delivery Server Model
 *
 * Handles email sending via Spotzee Email Proxy API with automatic bounce
 * and complaint handling through webhooks.
 *
 * @package MailWizz Extension
 * @author Spotzee Team <contact@spotzee.com>
 * @link https://spotzee.com
 * @copyright 2025 Spotzee Marketing
 * @license FSL-2.0 (Functional Source License 2.0)
 * @version 0.1.0
 */

class DeliveryServerSpotzeeWebApi extends DeliveryServer
{
    /**
     * Spotzee Email Proxy API endpoint
     */
    const EMAIL_PROXY_URL = 'https://emailproxy.spotzee.com/send';

    /**
     * Spotzee Email Proxy hostname
     */
    const EMAIL_PROXY_HOSTNAME = 'emailproxy.spotzee.com';

    /**
     * Email proxy URL for sending emails
     * @var string
     */
    public string $emailProxyUrl = self::EMAIL_PROXY_URL;

    /**
     * @var string
     */
    protected $serverType = 'spotzee-web-api';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://spotzee.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['username, password', 'required'],
            ['password', 'length', 'max' => 255],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'username'   => t('servers', 'Username'),
            'password'   => t('servers', 'Password'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'username' => t('servers', 'Your Spotzee email proxy username.'),
            'password' => t('servers', 'Your Spotzee email proxy password.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'username'  => t('servers', 'Username'),
            'password'  => t('servers', 'Password'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerSpotzeeWebApi the static model class
     */
    public static function model($className = __CLASS__)
    {
        /** @var DeliveryServerSpotzeeWebApi $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * Get MIME type for file using finfo
     *
     * @param string $filePath
     * @return string
     */
    protected function getMimeType(string $filePath): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        // Fallback to extension mapping if finfo fails
        if (!$mimeType) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'  => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt'  => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'svg'  => 'image/svg+xml',
                'txt'  => 'text/plain',
                'csv'  => 'text/csv',
                'json' => 'application/json',
                'xml'  => 'application/xml',
            ];

            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        }

        return $mimeType;
    }

    /**
     * Send email via Spotzee Email Proxy API
     *
     * @param array $params
     * @return array
     * @throws CException
     */
    public function send(array $params = []): array
    {
        /** @var array $params */
        $params = (array)hooks()->applyFilters('delivery_server_before_send_email', $this->getParamsArray($params), $this);

        if (!ArrayHelper::hasKeys($params, ['from', 'to', 'subject', 'body'])) {
            return [];
        }

        [$fromEmail, $fromName] = $this->getMailer()->findEmailAndName($params['from']);
        [$toEmail, $toName]     = $this->getMailer()->findEmailAndName($params['to']);

        if (!empty($params['fromName'])) {
            $fromName = $params['fromName'];
        }

        $replyToEmail = $replyToName = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail, $replyToName] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $sent = [];

        try {
            $this->from_name = empty($this->from_name) ? $this->from_email : $this->from_name;

            // Build return_path
            // For campaigns: bounce+{campaignUid}+{subscriberUid}@{from_domain}
            // For transactional: bounce@{from_domain}
            $fromDomain = substr(strrchr($fromEmail, "@"), 1);
            if (!empty($params['campaignUid']) && !empty($params['subscriberUid'])) {
                $returnPath = sprintf('bounce+%s+%s@%s',
                    $params['campaignUid'],
                    $params['subscriberUid'],
                    $fromDomain
                );
            } else {
                // Transactional emails - use simple bounce address
                $returnPath = 'bounce@' . $fromDomain;
            }

            // Build base payload
            $postData = [
                'to_name'        => $toName ?? $toEmail,
                'to_email'       => $toEmail,
                'from_name'      => !empty($fromName) ? $fromName : $this->from_name,
                'from_email'     => !empty($fromEmail) ? $fromEmail : $this->from_email,
                'reply_to_name'  => !empty($replyToName) ? $replyToName : $this->from_name,
                'reply_to_email' => !empty($replyToEmail) ? $replyToEmail : $this->from_email,
                'subject'        => $params['subject'],
                'body'           => !empty($params['body']) ? $params['body'] : '',
                'plain_text'     => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                'return_path'    => $returnPath,
            ];

            // Add custom headers (only X- prefixed headers)
            if (!empty($params['headers'])) {
                $customHeaders = [];
                $headers = $this->parseHeadersIntoKeyValue($params['headers']);
                foreach ($headers as $name => $value) {
                    // Only include headers starting with X- or x-
                    if (stripos($name, 'x-') === 0) {
                        $customHeaders[$name] = $value;
                    }
                }
                if (!empty($customHeaders)) {
                    $postData['custom_headers'] = $customHeaders;
                }
            }

            // Add attachments if present
            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_unique($params['attachments']);
                $postData['attachments'] = [];
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $postData['attachments'][] = [
                            'filename' => basename($attachment),
                            'content'  => base64_encode((string)file_get_contents($attachment)),
                            'type'     => $this->getMimeType($attachment),
                        ];
                    }
                }
            }

            // Create Basic Auth header
            $authHeader = 'Basic ' . base64_encode($this->username . ':' . $this->password);

            // Send request to email proxy
            $response = (new GuzzleHttp\Client())->post($this->emailProxyUrl, [
                'headers'   => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => $authHeader,
                ],
                'timeout'   => (int)$this->timeout,
                'json'      => $postData,
            ]);

            $responseBody = (string)$response->getBody();
            $responseData = json_decode($responseBody, true);

            // Check for successful queuing
            if (isset($responseData['status']) && $responseData['status'] === 'queued') {
                $messageId = $responseData['messageId'] ?? '';
                $this->getMailer()->addLog('OK - Message queued with ID: ' . $messageId);
                $sent = ['message_id' => $messageId];
            } else {
                throw new Exception('Unexpected response: ' . $responseBody);
            }
        } catch (GuzzleHttp\Exception\RequestException $e) {
            // Handle HTTP error responses
            $errorMessage = $e->getMessage();

            if ($e->hasResponse()) {
                $responseBody = (string)$e->getResponse()->getBody();
                $errorData = json_decode($responseBody, true);

                if (isset($errorData['error'])) {
                    $errorMessage = $errorData['error'];

                    // Add details if available (validation errors)
                    if (isset($errorData['details'])) {
                        $errorMessage .= ' - ' . $errorData['details'];
                    }
                }
            }

            $this->getMailer()->addLog($errorMessage);
        } catch (Exception $e) {
            $this->getMailer()->addLog($e->getMessage());
        }

        if ($sent) {
            $this->logUsage();
        }

        hooks()->doAction('delivery_server_after_send_email', $params, $this, $sent);

        return (array)$sent;
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = 'spotzee-web-api';
        return parent::getParamsArray($params);
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'hostname'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'signing_enabled'         => null,
            'max_connection_messages' => null,
            'bounce_server_id'        => null,
            'force_sender'            => null,
        ], $fields));
    }

    /**
     * Set default hostname after construct
     *
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->hostname = self::EMAIL_PROXY_HOSTNAME;
    }

    /**
     * @inheritDoc
     */
    public function getDswhUrl(): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url = $optionUrl->getFrontendUrl('dswh/spotzee-api/' . $this->server_id);
        if (is_cli()) {
            return $url;
        }
        if (request()->getIsSecureConnection() && parse_url($url, PHP_URL_SCHEME) == 'http') {
            $url = substr_replace($url, 'https', 0, 4);
        }
        return $url;
    }
}
