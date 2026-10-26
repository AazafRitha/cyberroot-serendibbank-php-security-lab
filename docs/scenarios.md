# Isolated security scenarios

The [validation matrix](../validation/README.md) links five proposed OWASP application exercises: broken access control, SQL injection, stored XSS, CSRF, and weak login. Each uses its own Compose project, localhost port, and fictional fixtures. They are independent of the core portal and its database. The `_template` remains available for a future PHP advisory after its CVE and affected versions are confirmed.

Every scenario README identifies the vulnerable banking area and reset command. Validation files give the input, expected evidence, secure comparison, and fix concept. Do not deploy the scenario stacks on the public VM by simply changing their host bindings. A challenge deployment needs separate isolation, participant scope, monitoring, and a reset process.
