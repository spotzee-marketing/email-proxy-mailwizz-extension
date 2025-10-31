<?php defined('MW_PATH') || exit('No direct script access allowed');

Yii::import('frontend.controllers.DswhController');

/**
 * Spotzee Email Proxy API Webhook Controller
 *
 * Handles webhook events from Spotzee Email Proxy API for bounce and complaint processing.
 *
 * @package MailWizz Extension
 * @author Spotzee Team <contact@spotzee.com>
 * @link https://spotzee.com
 * @copyright 2025 Spotzee Marketing
 * @license FSL-2.0 (Functional Source License 2.0)
 * @version 0.1.0
 */

class SpotzeeCustomExtFrontendDswhController extends DswhController
{
    /**
     * Webhook event types - Complaints
     */
    const EVENT_ABUSE_REPORT = 'incoming-report.abuse-report';
    const EVENT_FRAUD_REPORT = 'incoming-report.fraud-report';

    /**
     * Webhook event types - Bounces
     */
    const EVENT_DSN_PERM_FAIL = 'delivery.dsn-perm-fail';
    const EVENT_DSN_TEMP_FAIL = 'delivery.dsn-temp-fail';
    const EVENT_DELIVERY_FAILED = 'delivery.failed';

    /**
     * The extension instance
     * @var SpotzeeWebApiExt
     */
    public $extension;

    /**
     * Webhook endpoint action
     *
     * @param int $id Delivery server ID
     * @return void
     */
    public function actionIndex($id): void
    {
        $server = DeliveryServer::model()->findByPk((int)$id);

        if (empty($server)) {
            app()->end();
            return;
        }

        $map = [
            'amazon-ses-web-api'   => [$this, 'processAmazonSes'],
            'mailgun-web-api'      => [$this, 'processMailgun'],
            'sendgrid-web-api'     => [$this, 'processSendgrid'],
            'elasticemail-web-api' => [$this, 'processElasticemail'],
            'dyn-web-api'          => [$this, 'processDyn'],
            'sparkpost-web-api'    => [$this, 'processSparkpost'],
            'mailjet-web-api'      => [$this, 'processMailjet'],
            'sendinblue-web-api'   => [$this, 'processSendinblue'],
            'tipimail-web-api'     => [$this, 'processTipimail'],
            'pepipost-web-api'     => [$this, 'processPepipost'],
            'postmark-web-api'     => [$this, 'processPostmark'],
            'spotzee-web-api'      => [$this, 'processSpotzeeWebApi'],
        ];

        $map = (array)hooks()->applyFilters('dswh_process_map', $map, $server, $this);

        if (isset($map[$server->type]) && is_callable($map[$server->type])) {
            call_user_func_array($map[$server->type], [$server, $this]);
        }

        app()->end();
    }

    /**
     * Process Spotzee Email Proxy API webhook events
     *
     * @return void
     */
    public function processSpotzeeWebApi(): void
    {
        // Read raw JSON payload from request body
        $rawBody = file_get_contents('php://input');

        if (empty($rawBody)) {
            Yii::log('Spotzee webhook received empty request body', 'error', 'spotzee.webhook');
            app()->end();
            return;
        }

        // Parse JSON payload
        $payload = json_decode($rawBody, true);

        if (!$payload) {
            Yii::log('Spotzee webhook failed to parse JSON: ' . $rawBody, 'error', 'spotzee.webhook');
            app()->end();
            return;
        }

        // Support both single event and batch event formats
        $events = [];
        if (isset($payload['events']) && is_array($payload['events'])) {
            // Batch format: {"events": [...]}
            $events = $payload['events'];
        } elseif (isset($payload['type']) && isset($payload['data'])) {
            // Single event format: {id, type, data}
            $events = [$payload];
        } else {
            // Invalid format
            Yii::log('Spotzee webhook invalid event format: ' . json_encode($payload), 'error', 'spotzee.webhook');
            app()->end();
            return;
        }

        // Process each event in the payload
        foreach ($events as $event) {
            $this->processWebhookEvent($event);
        }

        app()->end();
        return;
    }

    /**
     * Process a single webhook event
     *
     * @param array $event
     * @return void
     */
    protected function processWebhookEvent(array $event): void
    {
        // Validate event structure
        if (!isset($event['type']) || !isset($event['data'])) {
            Yii::log('Spotzee webhook event missing type or data: ' . json_encode($event), 'error', 'spotzee.webhook');
            return;
        }

        $eventType = $event['type'];
        $eventData = $event['data'];

        // Extract return_path from data.from field
        if (!isset($eventData['from'])) {
            Yii::log('Spotzee webhook event missing from field: ' . json_encode($event), 'error', 'spotzee.webhook');
            return;
        }

        $returnPath = $eventData['from'];

        // Parse return_path format
        // Campaign emails: bounce+{campaignUid}+{subscriberUid}@domain.com
        // Transactional emails: bounce@domain.com
        $atPos = strpos($returnPath, '@');
        if ($atPos === false) {
            Yii::log('Spotzee webhook invalid return path format: ' . $returnPath, 'error', 'spotzee.webhook');
            return;
        }

        $localPart = substr($returnPath, 0, $atPos);
        $parts = explode('+', $localPart);

        // Check if this is a campaign email (has UIDs) or transactional email (no UIDs)
        if (count($parts) < 3 || $parts[0] !== 'bounce') {
            // This is a transactional email (bounce@domain.com format)
            // Transactional emails don't have campaign/subscriber context, so we can't process them
            // Just log and return
            return;
        }

        $campaignUid = $parts[1];
        $subscriberUid = $parts[2];

        // Look up campaign
        $campaign = Campaign::model()->findByAttributes([
            'campaign_uid' => $campaignUid,
        ]);

        if (empty($campaign)) {
            Yii::log('Spotzee webhook campaign not found: ' . $campaignUid, 'warning', 'spotzee.webhook');
            return;
        }

        // Look up subscriber
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'        => $campaign->list_id,
            'subscriber_uid' => $subscriberUid,
            'status'         => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            Yii::log('Spotzee webhook subscriber not found: ' . $subscriberUid . ' for campaign: ' . $campaignUid, 'warning', 'spotzee.webhook');
            return;
        }

        // Check for duplicate events - same as SendGrid/Mailgun approach
        // Check by campaign_id + subscriber_id to prevent duplicate processing
        $existingBounce = CampaignBounceLog::model()->countByAttributes([
            'campaign_id'   => (int)$campaign->campaign_id,
            'subscriber_id' => (int)$subscriber->subscriber_id,
        ]);

        if (!empty($existingBounce)) {
            // Duplicate event - ignore to prevent reprocessing
            Yii::log('Spotzee webhook duplicate event ignored for campaign: ' . $campaignUid . ', subscriber: ' . $subscriberUid, 'info', 'spotzee.webhook');
            return;
        }

        // Determine if this is a complaint or bounce event
        $isComplaint = $this->isComplaintEvent($eventType);
        $bounceType = $this->getBounceType($eventType);

        // Extract error/failure message
        $errorMessage = $this->extractErrorMessage($eventType, $eventData);

        // Process complaints
        if ($isComplaint) {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);

            // Blacklist subscriber - store error message directly without prefix
            $subscriber->addToBlacklist($errorMessage);

            return;
        }

        // Process bounces
        if ($bounceType !== null) {
            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
            $bounceLog->message       = $errorMessage;
            $bounceLog->bounce_type   = $bounceType;
            $bounceLog->save();

            // Blacklist on hard bounces only
            if ($bounceType === CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }
        }
    }

    /**
     * Check if event is a complaint
     *
     * @param string $eventType
     * @return bool
     */
    protected function isComplaintEvent(string $eventType): bool
    {
        $complaintEvents = [
            self::EVENT_ABUSE_REPORT,
            self::EVENT_FRAUD_REPORT,
        ];

        return in_array($eventType, $complaintEvents);
    }

    /**
     * Get bounce type based on event type
     *
     * @param string $eventType
     * @return string|null
     */
    protected function getBounceType(string $eventType): ?string
    {
        $bounceMapping = [
            self::EVENT_DSN_PERM_FAIL    => CampaignBounceLog::BOUNCE_HARD,
            self::EVENT_DSN_TEMP_FAIL    => CampaignBounceLog::BOUNCE_SOFT,
            self::EVENT_DELIVERY_FAILED  => CampaignBounceLog::BOUNCE_INTERNAL,
        ];

        return $bounceMapping[$eventType] ?? null;
    }

    /**
     * Extract error message from event data based on event type
     *
     * @param string $eventType
     * @param array $eventData
     * @return string
     */
    protected function extractErrorMessage(string $eventType, array $eventData): string
    {
        // For delivery.failed events, use 'reason' field
        if ($eventType === self::EVENT_DELIVERY_FAILED && isset($eventData['reason'])) {
            return $eventData['reason'];
        }

        // For DSN bounces, use 'details' field (contains full SMTP error)
        if (isset($eventData['details'])) {
            // Details can be a string or array
            if (is_array($eventData['details'])) {
                return implode('; ', $eventData['details']);
            }
            return $eventData['details'];
        }

        // For complaints, construct message from available data
        if ($this->isComplaintEvent($eventType)) {
            $parts = [];

            if (isset($eventData['hostname'])) {
                $parts[] = 'Reporter: ' . $eventData['hostname'];
            }

            if (isset($eventData['remoteIp'])) {
                $parts[] = 'IP: ' . $eventData['remoteIp'];
            }

            if (isset($eventData['result'])) {
                $parts[] = 'Result: ' . $eventData['result'];
            }

            return !empty($parts) ? implode(', ', $parts) : 'Complaint received';
        }

        // Fallback
        return 'No details provided';
    }
}
