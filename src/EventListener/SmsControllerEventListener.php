<?php

declare(strict_types=1);

namespace BikeShare\EventListener;

use BikeShare\Db\DbInterface;
use BikeShare\SmsConnector\SmsConnectorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class SmsControllerEventListener
{
    private DbInterface $db;
    private SmsConnectorInterface $smsConnector;
    private LoggerInterface $logger;

    public function __construct(
        DbInterface $db,
        SmsConnectorInterface $smsConnector,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->smsConnector = $smsConnector;
        $this->logger = $logger;
    }

    public function __invoke(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        //temp logger just for case if something will goes wrong
        $this->logger->info('SMS received', ['request' => $_REQUEST]);
        $this->smsConnector->receive();

        $sms_uuid = $this->db->escape($this->smsConnector->getUUID());
        $sender = $this->db->escape($this->smsConnector->getNumber());
        $receive_time = $this->db->escape($this->smsConnector->getTime());
        $sms_text = $this->db->escape($this->smsConnector->getMessage());
        $ip = $this->db->escape($this->smsConnector->getIPAddress());

        $result = $this->db->query("SELECT sms_uuid FROM received WHERE sms_uuid='$sms_uuid'");
        if ($result->rowCount() >= 1) {
            // sms already exists in DB, possible problem
            $this->logger->error("SMS already exists in DB", compact('sms_uuid'));
            //notifyAdmins(_('Problem with SMS') . " $sms_uuid!", 1);
        } else {
            $this->db->query(
                "INSERT INTO received SET 
                 sms_uuid='$sms_uuid',
                 sender='$sender',
                 receive_time='$receive_time',
                 sms_text='$sms_text',
                 ip='$ip'"
            );
        }

        $this->db->commit();
    }
}
