# Code inventory — OpenpayPhp 1.6.1

**Spec**: [`spec.md`](spec.md)  
**Last audited**: 2026-11-16  
**Production units**: 17 (every file tracked under `src/`; audit: `git ls-files src | wc -l`)

Worker audit: [docs/FRANKENPHP-WORKER-AUDIT.md](../../docs/FRANKENPHP-WORKER-AUDIT.md).

## Credentials and session

| Source path | Spec section | Requirement IDs |
| --- | --- | --- |
| `src/Credentials.php` | Credentials and session | FR-CRED-001, FR-RUN-001 |
| `src/Country.php` | Credentials and session | FR-CRED-002 |
| `src/Session.php` | Credentials and session | FR-CRED-003, FR-RUN-001 |

## Client and resources

| Source path | Spec section | Requirement IDs |
| --- | --- | --- |
| `src/Client.php` | Client and resources | FR-API-001, FR-RUN-001 |
| `src/Api/ResourceApi.php` | Client and resources | FR-API-002 |
| `src/Api/ChargesApiInterface.php` | Client and resources | FR-API-004 |
| `src/Version.php` | Client and resources | FR-API-003 |

## HTTP transport

| Source path | Spec section | Requirement IDs |
| --- | --- | --- |
| `src/Http/HttpClient.php` | HTTP transport | FR-HTTP-001 |
| `src/Http/HttpResponse.php` | HTTP transport | FR-HTTP-001 |
| `src/Http/CurlHttpClient.php` | HTTP transport | FR-HTTP-002 |
| `src/Http/Psr18HttpClient.php` | HTTP transport | FR-HTTP-003 |

## Errors

| Source path | Spec section | Requirement IDs |
| --- | --- | --- |
| `src/Exception/OpenpayException.php` | Errors | FR-ERR-001 |
| `src/Exception/OpenpayApiException.php` | Errors | FR-ERR-002 |
| `src/Exception/OpenpayConfigurationException.php` | Errors | FR-ERR-003 |
| `src/Exception/OpenpayInvalidRequestException.php` | Errors | FR-ERR-003 |
| `src/Exception/OpenpayInvalidResponseException.php` | Errors | FR-ERR-003 |
| `src/Exception/OpenpayTransportException.php` | Errors | FR-ERR-001, FR-ERR-003 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Credentials and session | 3 | 3 |
| Client and resources | 4 | 4 |
| HTTP transport | 4 | 4 |
| Errors | 6 | 6 |
| **Total production files (`git ls-files src`)** | **17** | **17** |
