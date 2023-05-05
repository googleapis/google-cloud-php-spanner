<?php
/*
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * GENERATED CODE WARNING
 * This file was automatically generated - do not edit!
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

// [START spanner_v1_generated_Spanner_Read_sync]
use Google\ApiCore\ApiException;
use Google\Cloud\Spanner\V1\KeySet;
use Google\Cloud\Spanner\V1\ResultSet;
use Google\Cloud\Spanner\V1\SpannerClient;

/**
 * Reads rows from the database using key lookups and scans, as a
 * simple key/value style alternative to
 * [ExecuteSql][google.spanner.v1.Spanner.ExecuteSql].  This method cannot be used to
 * return a result set larger than 10 MiB; if the read matches more
 * data than that, the read fails with a `FAILED_PRECONDITION`
 * error.
 *
 * Reads inside read-write transactions might return `ABORTED`. If
 * this occurs, the application should restart the transaction from
 * the beginning. See [Transaction][google.spanner.v1.Transaction] for more details.
 *
 * Larger result sets can be yielded in streaming fashion by calling
 * [StreamingRead][google.spanner.v1.Spanner.StreamingRead] instead.
 *
 * @param string $formattedSession The session in which the read should be performed. Please see
 *                                 {@see SpannerClient::sessionName()} for help formatting this field.
 * @param string $table            The name of the table in the database to be read.
 * @param string $columnsElement   The columns of [table][google.spanner.v1.ReadRequest.table] to be returned for each row matching
 *                                 this request.
 */
function read_sample(string $formattedSession, string $table, string $columnsElement): void
{
    // Create a client.
    $spannerClient = new SpannerClient();

    // Prepare any non-scalar elements to be passed along with the request.
    $columns = [$columnsElement,];
    $keySet = new KeySet();

    // Call the API and handle any network failures.
    try {
        /** @var ResultSet $response */
        $response = $spannerClient->read($formattedSession, $table, $columns, $keySet);
        printf('Response data: %s' . PHP_EOL, $response->serializeToJsonString());
    } catch (ApiException $ex) {
        printf('Call failed with message: %s' . PHP_EOL, $ex->getMessage());
    }
}

/**
 * Helper to execute the sample.
 *
 * This sample has been automatically generated and should be regarded as a code
 * template only. It will require modifications to work:
 *  - It may require correct/in-range values for request initialization.
 *  - It may require specifying regional endpoints when creating the service client,
 *    please see the apiEndpoint client configuration option for more details.
 */
function callSample(): void
{
    $formattedSession = SpannerClient::sessionName(
        '[PROJECT]',
        '[INSTANCE]',
        '[DATABASE]',
        '[SESSION]'
    );
    $table = '[TABLE]';
    $columnsElement = '[COLUMNS]';

    read_sample($formattedSession, $table, $columnsElement);
}
// [END spanner_v1_generated_Spanner_Read_sync]
