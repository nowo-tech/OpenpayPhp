# Coverage

`phpunit.xml.dist` includes `src/`. Gate ≥ **99%** Lines via `.scripts/php-coverage-percent.sh`.

Justified `@codeCoverageIgnore`: `curl_init`/`curl_exec` failure and `json_encode` false branches.
