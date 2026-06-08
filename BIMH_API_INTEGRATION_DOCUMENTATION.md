# BIMH API Integration Documentation

## Document Control

- Project: `PWD Asset Database`
- Integration Subject: `BIMH Establishment Data Lookup`
- Prepared For: `External BIMH Service Provider / API Team`
- Prepared By: `PWD Asset Database Team`
- Format: `Markdown working draft for review and technical alignment`
- Status: `Draft for provider confirmation`

## 1. Purpose

This document describes the required integration between the `PWD Asset Database` and the `BIMH` platform so that a user can enter a `BIMH ID` and retrieve approved establishment attributes from the BIMH system.

The purpose of this integration is to:

- reduce manual data entry
- improve correctness of structural reference data
- standardize establishment information
- make bulk import review faster and safer for users

## 2. Functional Summary

The `PWD Asset Database` will introduce a new asset field type named `BIMH`.

When a user enters a valid `BIMH ID`, the system will:

1. send a request to the BIMH API
2. validate the BIMH ID
3. retrieve selected establishment attributes
4. show those attributes as read-only values in the user interface
5. store the returned values in the local application database

The fetched values are intended to be used later from the local database for:

- asset table display
- edit/view workflows
- exports
- audit review
- history and reporting

The local system will not rely on live API calls for ordinary table rendering.

## 3. User Experience and Operating Flow

### 3.1 Manual Add Asset / Edit Asset

For a field configured as `BIMH`:

- the user will edit only the `BIMH ID`
- a small fetch icon/button will be placed beside the BIMH input
- after the BIMH field, a set of read-only attribute fields will be shown
- the read-only fields will be populated only from the BIMH API response

The user will not directly edit the fetched BIMH attributes.

### 3.2 Excel Bulk Upload

In Excel import:

- users will upload only the `BIMH ID`
- fetched BIMH attributes will not be included in the import template as editable columns

After the existing import audit completes and the audit modal opens:

- the system will automatically process BIMH IDs row by row
- the system will fetch BIMH attributes for each row
- the BIMH attribute columns in the audit modal will be read-only
- each row will still have a fetch button beside the BIMH ID for retry or correction

The user may edit only the `BIMH ID`.

## 4. Authentication Requirements

Based on the provider note received, the integration is expected to use OAuth 2.0 bearer-token authentication.

### 4.1 Token Method

- Auth Type: `OAuth 2.0`
- Token Method: `POST`
- Token Endpoint: `{base-url}/api/token`
- Content-Type: `application/x-www-form-urlencoded`
- Authorization Header: `Basic {hash_value_key}`

### 4.2 Token Request Parameters

Expected fields:

- `grant_type`
- `username`
- `password`

### 4.3 Subsequent API Requests

All functional BIMH API requests are expected to include:

- `Authorization: Bearer {access_token}`

## 5. Business API Requirement for BIMH Lookup

The PWD Asset Database needs an API endpoint that can return establishment data for a single BIMH establishment ID.

### 5.1 Required Business Objective

Given a valid BIMH establishment identifier, the API should return the official establishment profile so the local system can populate configured read-only fields.

### 5.2 Expected Request Model

The provider note indicates the establishment data API uses:

- `requestId`
- `ministryCode`

However, the exact request parameter for the BIMH establishment identifier still needs provider confirmation.

### 5.3 Expected Transport

- Method: `GET` or provider-confirmed supported method
- Format: query parameters or JSON, as confirmed by provider
- Security: TLS over HTTPS only

## 6. Expected Response Structure

The local system expects a JSON response containing establishment data in a structure comparable to:

```json
{
  "requestId": "abcd1234efgh5678ijkl9012mnop3456",
  "data": [
    {
      "establishmentId": "EST-00001",
      "establishmentName": "PWD Head Office",
      "projectName": "PWD Modernization Project",
      "concernedMinistry": "Ministry of Housing and Public Works",
      "constructedBy": "PWD",
      "administrativeLocation": {
        "division": "Dhaka",
        "district": "Dhaka",
        "upazila": "Ramna",
        "union": null
      },
      "gpsCoordinates": {
        "latitude": "23.7382",
        "longitude": "90.3952"
      },
      "constructionCompletionDate": "2020-12-31",
      "structureType": "RC Frame",
      "foundationType": "Pile",
      "buildingCondition": "Good"
    }
  ]
}
```

## 7. Expected Field Mapping Pattern

The `PWD Asset Database` will allow superadmin to choose which BIMH attributes should be pulled for a BIMH field.

Typical examples include:

- `establishmentName`
- `constructionCompletionDate`
- `gpsCoordinates.latitude`
- `gpsCoordinates.longitude`
- `structureType`
- `foundationType`
- `buildingCondition`

The exact supported attribute list will be finalized after provider confirmation and internal mapping approval.

## 8. Validation and Error Handling Expectations

The local system requires clear operational behavior for the following cases.

### 8.1 Valid BIMH ID

Expected behavior:

- API returns one matching establishment
- system populates configured attributes
- user can save the asset

### 8.2 Invalid BIMH ID / No Match

Expected behavior:

- no matching establishment returned
- system marks the BIMH attribute fields with a clear status such as `Invalid BIMH ID`
- user remains able to correct the BIMH ID and fetch again

### 8.3 Authentication Failure

Expected behavior:

- system should receive a clear auth-related error
- UI should display a professional retry-safe message such as `Authentication error` or `Token request failed`

### 8.4 Connection / Timeout / Upstream Error

Expected behavior:

- system should receive a machine-detectable failure condition
- UI should display a professional message such as `Connection error`
- the user should be able to retry fetch

### 8.5 Ambiguous / Multiple Results

Expected behavior:

- provider should clarify whether multiple results are possible
- preferred behavior is that the business API returns a single authoritative establishment record for one BIMH ID

## 9. Security Requirements

To protect both systems and users, the following controls are requested.

### 9.1 Transport Security

- HTTPS only
- no plaintext credential exchange

### 9.2 Credential Handling

- provider credentials must be environment-safe and rotatable
- no long-term secrets should appear in logs, screenshots, or user-facing messages

### 9.3 Token Handling

- bearer tokens should have a reasonable expiry
- refresh behavior should be documented
- unauthorized responses should be clearly distinguishable from connectivity failures

### 9.4 Least Exposure

Only the minimum necessary data required by the selected BIMH attributes should be relied upon by the consuming system.

### 9.5 Provider Logging and Audit

Provider should confirm:

- whether requests are logged
- what identifiers are logged
- whether request IDs can be used for joint troubleshooting

## 10. Performance and User Convenience Requirements

The user experience must remain manageable during both manual entry and bulk audit.

### 10.1 Manual Entry

- fetch should complete within a practical user-facing time window
- repeated clicks should not create inconsistent results

### 10.2 Bulk Audit

Since audit modal auto-fetch will process BIMH IDs row by row:

- provider should confirm safe request pacing
- provider should confirm any rate limits
- provider should confirm whether sequential requests are preferred

### 10.3 Stability

The local application should be able to:

- retry fetch on corrected BIMH ID
- recover gracefully from timeout or upstream failure
- preserve already fetched values in local DB after successful save

## 11. Deliverables Required From BIMH Service Provider

The following deliverables are required to finalize and safely implement the integration.

### 11.1 Base Connectivity Details

Provide in plain text or structured markdown:

- production base URL
- test / sandbox base URL
- whether IP whitelisting is required
- whether VPN or private network access is required

### 11.2 Authentication Specification

Provide in markdown or PDF with exact examples:

- token endpoint URL
- auth header construction rule for `Basic {hash_value_key}`
- required token request parameters
- sample token request
- sample token success response
- sample token failure response
- refresh token behavior, if applicable

### 11.3 BIMH Lookup API Contract

Provide in markdown, PDF, or OpenAPI/Swagger format:

- exact endpoint URL for BIMH establishment lookup
- HTTP method
- exact request parameter name for BIMH establishment ID
- request schema
- required headers
- request examples
- response examples
- error response examples

### 11.4 Data Dictionary

Provide in tabular format (`xlsx`, `csv`, markdown table, or PDF):

- response field name
- meaning / business definition
- data type
- nullable or not
- example value
- whether the field is stable and supported for long-term integration

### 11.5 Error Model

Provide in structured documentation:

- HTTP status codes used
- application-level error codes used
- invalid ID behavior
- auth failure behavior
- timeout / upstream failure behavior
- throttling / rate-limit behavior

### 11.6 Usage Constraints

Provide in writing:

- request rate limits
- concurrency limits
- retry recommendations
- maintenance window policy
- provider contact for incident escalation

### 11.7 Test Assets

Provide in safe non-production format if possible:

- sample valid BIMH IDs
- sample invalid BIMH IDs
- sample edge-case BIMH IDs
- expected responses for each sample

### 11.8 Change Management

Provide in writing:

- how API version changes are announced
- deprecation notice period
- support channel and turnaround expectation

## 12. Proposed Local Mapping Workflow

After provider confirmation, the local system will maintain an internal BIMH attribute catalog and map each selected response field to a read-only companion field in the asset system.

This allows:

- safe local storage
- stable reporting
- future UI loads without repeated remote calls

## 13. Open Items Requiring Provider Confirmation

The following points remain open:

1. exact BIMH lookup endpoint
2. exact request parameter carrying BIMH establishment ID
3. final auth header/hash generation procedure
4. sandbox/test environment access details
5. definitive response field list and stability
6. rate-limit and retry policy

## 14. Recommended Next Step

Please review this document and provide the deliverables listed in Section 11 in a machine-readable and example-rich format. Once received, the consuming application team will finalize implementation and field mapping.
