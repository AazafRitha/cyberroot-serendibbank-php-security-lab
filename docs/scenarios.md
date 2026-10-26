# Isolated security scenarios

The [main site](../validation/main-site.md) now includes the proposed OWASP application exercises when `APP_LAB_MODE=1`: broken access control, SQL injection, stored XSS, CSRF, and weak login. The [validation matrix](../validation/README.md) also links standalone versions. Each standalone version uses its own Compose project, localhost port, and fictional fixtures. The `_template` remains available for a future PHP advisory after its CVE and affected versions are confirmed.

Every scenario README identifies the vulnerable banking area and reset command. Validation files give the input, expected evidence, secure comparison, and fix concept. Do not deploy the scenario stacks on the public VM by simply changing their host bindings. A challenge deployment needs separate isolation, participant scope, monitoring, and a reset process.
