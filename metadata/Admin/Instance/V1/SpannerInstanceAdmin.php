<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/spanner/admin/instance/v1/spanner_instance_admin.proto

namespace GPBMetadata\Google\Spanner\Admin\Instance\V1;

class SpannerInstanceAdmin
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        \GPBMetadata\Google\Iam\V1\IamPolicy::initOnce();
        \GPBMetadata\Google\Iam\V1\Policy::initOnce();
        \GPBMetadata\Google\Longrunning\Operations::initOnce();
        \GPBMetadata\Google\Protobuf\GPBEmpty::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Protobuf\Timestamp::initOnce();
        \GPBMetadata\Google\Spanner\Admin\Instance\V1\Common::initOnce();
        $pool->internalAddGeneratedFile(
            '
�F
=google/spanner/admin/instance/v1/spanner_instance_admin.proto google.spanner.admin.instance.v1google/api/client.protogoogle/api/field_behavior.protogoogle/api/resource.protogoogle/iam/v1/iam_policy.protogoogle/iam/v1/policy.proto#google/longrunning/operations.protogoogle/protobuf/empty.proto google/protobuf/field_mask.protogoogle/protobuf/timestamp.proto-google/spanner/admin/instance/v1/common.proto"�
ReplicaInfo
location (	G
type (29.google.spanner.admin.instance.v1.ReplicaInfo.ReplicaType
default_leader_location ("O
ReplicaType
TYPE_UNSPECIFIED 

READ_WRITE
	READ_ONLY
WITNESS"�
InstanceConfig
name (	
display_name (	O
config_type (25.google.spanner.admin.instance.v1.InstanceConfig.TypeB�A?
replicas (2-.google.spanner.admin.instance.v1.ReplicaInfoM
optional_replicas (2-.google.spanner.admin.instance.v1.ReplicaInfoB�A?
base_config (	B*�A\'
%spanner.googleapis.com/InstanceConfigL
labels (2<.google.spanner.admin.instance.v1.InstanceConfig.LabelsEntry
etag	 (	
leader_options (	
reconciling
 (B�AJ
state (26.google.spanner.admin.instance.v1.InstanceConfig.StateB�A-
LabelsEntry
key (	
value (	:8"B
Type
TYPE_UNSPECIFIED 
GOOGLE_MANAGED
USER_MANAGED"7
State
STATE_UNSPECIFIED 
CREATING	
READY:`�A]
%spanner.googleapis.com/InstanceConfig4projects/{project}/instanceConfigs/{instance_config}"�
AutoscalingConfigf
autoscaling_limits (2E.google.spanner.admin.instance.v1.AutoscalingConfig.AutoscalingLimitsB�Ah
autoscaling_targets (2F.google.spanner.admin.instance.v1.AutoscalingConfig.AutoscalingTargetsB�A�
AutoscalingLimits
	min_nodes (H 
min_processing_units (H 
	max_nodes (H
max_processing_units (HB
	min_limitB
	max_limitr
AutoscalingTargets2
%high_priority_cpu_utilization_percent (B�A(
storage_utilization_percent (B�A"�
Instance
name (	B�A=
config (	B-�A�A\'
%spanner.googleapis.com/InstanceConfig
display_name (	B�A

node_count (
processing_units	 (T
autoscaling_config (23.google.spanner.admin.instance.v1.AutoscalingConfigB�AD
state (20.google.spanner.admin.instance.v1.Instance.StateB�AF
labels (26.google.spanner.admin.instance.v1.Instance.LabelsEntry
endpoint_uris (	4
create_time (2.google.protobuf.TimestampB�A4
update_time (2.google.protobuf.TimestampB�A-
LabelsEntry
key (	
value (	:8"7
State
STATE_UNSPECIFIED 
CREATING	
READY:M�AJ
spanner.googleapis.com/Instance\'projects/{project}/instances/{instance}"�
ListInstanceConfigsRequestC
parent (	B3�A�A-
+cloudresourcemanager.googleapis.com/Project
	page_size (

page_token (	"�
ListInstanceConfigsResponseJ
instance_configs (20.google.spanner.admin.instance.v1.InstanceConfig
next_page_token (	"W
GetInstanceConfigRequest;
name (	B-�A�A\'
%spanner.googleapis.com/InstanceConfig"�
CreateInstanceConfigRequestC
parent (	B3�A�A-
+cloudresourcemanager.googleapis.com/Project
instance_config_id (	B�AN
instance_config (20.google.spanner.admin.instance.v1.InstanceConfigB�A
validate_only ("�
UpdateInstanceConfigRequestN
instance_config (20.google.spanner.admin.instance.v1.InstanceConfigB�A4
update_mask (2.google.protobuf.FieldMaskB�A
validate_only ("
DeleteInstanceConfigRequest;
name (	B-�A�A\'
%spanner.googleapis.com/InstanceConfig
etag (	
validate_only ("�
#ListInstanceConfigOperationsRequestC
parent (	B3�A�A-
+cloudresourcemanager.googleapis.com/Project
filter (	
	page_size (

page_token (	"r
$ListInstanceConfigOperationsResponse1

operations (2.google.longrunning.Operation
next_page_token (	"{
GetInstanceRequest5
name (	B\'�A�A!
spanner.googleapis.com/Instance.

field_mask (2.google.protobuf.FieldMask"�
CreateInstanceRequestC
parent (	B3�A�A-
+cloudresourcemanager.googleapis.com/Project
instance_id (	B�AA
instance (2*.google.spanner.admin.instance.v1.InstanceB�A"�
ListInstancesRequestC
parent (	B3�A�A-
+cloudresourcemanager.googleapis.com/Project
	page_size (

page_token (	
filter (	"o
ListInstancesResponse=
	instances (2*.google.spanner.admin.instance.v1.Instance
next_page_token (	"�
UpdateInstanceRequestA
instance (2*.google.spanner.admin.instance.v1.InstanceB�A3

field_mask (2.google.protobuf.FieldMaskB�A"N
DeleteInstanceRequest5
name (	B\'�A�A!
spanner.googleapis.com/Instance"�
CreateInstanceMetadata<
instance (2*.google.spanner.admin.instance.v1.Instance.

start_time (2.google.protobuf.Timestamp/
cancel_time (2.google.protobuf.Timestamp,
end_time (2.google.protobuf.Timestamp"�
UpdateInstanceMetadata<
instance (2*.google.spanner.admin.instance.v1.Instance.

start_time (2.google.protobuf.Timestamp/
cancel_time (2.google.protobuf.Timestamp,
end_time (2.google.protobuf.Timestamp"�
CreateInstanceConfigMetadataI
instance_config (20.google.spanner.admin.instance.v1.InstanceConfigE
progress (23.google.spanner.admin.instance.v1.OperationProgress/
cancel_time (2.google.protobuf.Timestamp"�
UpdateInstanceConfigMetadataI
instance_config (20.google.spanner.admin.instance.v1.InstanceConfigE
progress (23.google.spanner.admin.instance.v1.OperationProgress/
cancel_time (2.google.protobuf.Timestamp2�
InstanceAdmin�
ListInstanceConfigs<.google.spanner.admin.instance.v1.ListInstanceConfigsRequest=.google.spanner.admin.instance.v1.ListInstanceConfigsResponse"8�Aparent���)\'/v1/{parent=projects/*}/instanceConfigs�
GetInstanceConfig:.google.spanner.admin.instance.v1.GetInstanceConfigRequest0.google.spanner.admin.instance.v1.InstanceConfig"6�Aname���)\'/v1/{name=projects/*/instanceConfigs/*}�
CreateInstanceConfig=.google.spanner.admin.instance.v1.CreateInstanceConfigRequest.google.longrunning.Operation"��Ap
/google.spanner.admin.instance.v1.InstanceConfig=google.spanner.admin.instance.v1.CreateInstanceConfigMetadata�A)parent,instance_config,instance_config_id���,"\'/v1/{parent=projects/*}/instanceConfigs:*�
UpdateInstanceConfig=.google.spanner.admin.instance.v1.UpdateInstanceConfigRequest.google.longrunning.Operation"��Ap
/google.spanner.admin.instance.v1.InstanceConfig=google.spanner.admin.instance.v1.UpdateInstanceConfigMetadata�Ainstance_config,update_mask���<27/v1/{instance_config.name=projects/*/instanceConfigs/*}:*�
DeleteInstanceConfig=.google.spanner.admin.instance.v1.DeleteInstanceConfigRequest.google.protobuf.Empty"6�Aname���)*\'/v1/{name=projects/*/instanceConfigs/*}�
ListInstanceConfigOperationsE.google.spanner.admin.instance.v1.ListInstanceConfigOperationsRequestF.google.spanner.admin.instance.v1.ListInstanceConfigOperationsResponse"A�Aparent���20/v1/{parent=projects/*}/instanceConfigOperations�
ListInstances6.google.spanner.admin.instance.v1.ListInstancesRequest7.google.spanner.admin.instance.v1.ListInstancesResponse"2�Aparent���#!/v1/{parent=projects/*}/instances�
GetInstance4.google.spanner.admin.instance.v1.GetInstanceRequest*.google.spanner.admin.instance.v1.Instance"0�Aname���#!/v1/{name=projects/*/instances/*}�
CreateInstance7.google.spanner.admin.instance.v1.CreateInstanceRequest.google.longrunning.Operation"��Ad
)google.spanner.admin.instance.v1.Instance7google.spanner.admin.instance.v1.CreateInstanceMetadata�Aparent,instance_id,instance���&"!/v1/{parent=projects/*}/instances:*�
UpdateInstance7.google.spanner.admin.instance.v1.UpdateInstanceRequest.google.longrunning.Operation"��Ad
)google.spanner.admin.instance.v1.Instance7google.spanner.admin.instance.v1.UpdateInstanceMetadata�Ainstance,field_mask���/2*/v1/{instance.name=projects/*/instances/*}:*�
DeleteInstance7.google.spanner.admin.instance.v1.DeleteInstanceRequest.google.protobuf.Empty"0�Aname���#*!/v1/{name=projects/*/instances/*}�
SetIamPolicy".google.iam.v1.SetIamPolicyRequest.google.iam.v1.Policy"O�Aresource,policy���7"2/v1/{resource=projects/*/instances/*}:setIamPolicy:*�
GetIamPolicy".google.iam.v1.GetIamPolicyRequest.google.iam.v1.Policy"H�Aresource���7"2/v1/{resource=projects/*/instances/*}:getIamPolicy:*�
TestIamPermissions(.google.iam.v1.TestIamPermissionsRequest).google.iam.v1.TestIamPermissionsResponse"Z�Aresource,permissions���="8/v1/{resource=projects/*/instances/*}:testIamPermissions:*x�Aspanner.googleapis.com�A\\https://www.googleapis.com/auth/cloud-platform,https://www.googleapis.com/auth/spanner.adminB�
$com.google.spanner.admin.instance.v1BSpannerInstanceAdminProtoPZFcloud.google.com/go/spanner/admin/instance/apiv1/instancepb;instancepb�&Google.Cloud.Spanner.Admin.Instance.V1�&Google\\Cloud\\Spanner\\Admin\\Instance\\V1�+Google::Cloud::Spanner::Admin::Instance::V1bproto3'
        , true);

        static::$is_initialized = true;
    }
}

