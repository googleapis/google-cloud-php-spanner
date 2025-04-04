<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/spanner/admin/database/v1/backup.proto

namespace Google\Cloud\Spanner\Admin\Database\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Instance partition information for the backup.
 *
 * Generated from protobuf message <code>google.spanner.admin.database.v1.BackupInstancePartition</code>
 */
class BackupInstancePartition extends \Google\Protobuf\Internal\Message
{
    /**
     * A unique identifier for the instance partition. Values are of the form
     * `projects/<project>/instances/<instance>/instancePartitions/<instance_partition_id>`
     *
     * Generated from protobuf field <code>string instance_partition = 1 [(.google.api.resource_reference) = {</code>
     */
    private $instance_partition = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $instance_partition
     *           A unique identifier for the instance partition. Values are of the form
     *           `projects/<project>/instances/<instance>/instancePartitions/<instance_partition_id>`
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Spanner\Admin\Database\V1\Backup::initOnce();
        parent::__construct($data);
    }

    /**
     * A unique identifier for the instance partition. Values are of the form
     * `projects/<project>/instances/<instance>/instancePartitions/<instance_partition_id>`
     *
     * Generated from protobuf field <code>string instance_partition = 1 [(.google.api.resource_reference) = {</code>
     * @return string
     */
    public function getInstancePartition()
    {
        return $this->instance_partition;
    }

    /**
     * A unique identifier for the instance partition. Values are of the form
     * `projects/<project>/instances/<instance>/instancePartitions/<instance_partition_id>`
     *
     * Generated from protobuf field <code>string instance_partition = 1 [(.google.api.resource_reference) = {</code>
     * @param string $var
     * @return $this
     */
    public function setInstancePartition($var)
    {
        GPBUtil::checkString($var, True);
        $this->instance_partition = $var;

        return $this;
    }

}

