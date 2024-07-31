<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/spanner/admin/database/v1/backup_schedule.proto

namespace Google\Cloud\Spanner\Admin\Database\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Defines specifications of the backup schedule.
 *
 * Generated from protobuf message <code>google.spanner.admin.database.v1.BackupScheduleSpec</code>
 */
class BackupScheduleSpec extends \Google\Protobuf\Internal\Message
{
    protected $schedule_spec;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Cloud\Spanner\Admin\Database\V1\CrontabSpec $cron_spec
     *           Cron style schedule specification.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Spanner\Admin\Database\V1\BackupSchedule::initOnce();
        parent::__construct($data);
    }

    /**
     * Cron style schedule specification.
     *
     * Generated from protobuf field <code>.google.spanner.admin.database.v1.CrontabSpec cron_spec = 1;</code>
     * @return \Google\Cloud\Spanner\Admin\Database\V1\CrontabSpec|null
     */
    public function getCronSpec()
    {
        return $this->readOneof(1);
    }

    public function hasCronSpec()
    {
        return $this->hasOneof(1);
    }

    /**
     * Cron style schedule specification.
     *
     * Generated from protobuf field <code>.google.spanner.admin.database.v1.CrontabSpec cron_spec = 1;</code>
     * @param \Google\Cloud\Spanner\Admin\Database\V1\CrontabSpec $var
     * @return $this
     */
    public function setCronSpec($var)
    {
        GPBUtil::checkMessage($var, \Google\Cloud\Spanner\Admin\Database\V1\CrontabSpec::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getScheduleSpec()
    {
        return $this->whichOneof("schedule_spec");
    }

}
