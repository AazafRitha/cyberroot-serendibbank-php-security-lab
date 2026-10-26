"""Check one running localhost scenario: python validation/check-scenario.py NAME."""

import http.cookiejar
import sys
import urllib.parse
import urllib.request

name = sys.argv[1]
ports = {"broken-access-control": 8081, "sql-injection": 8082, "stored-xss": 8083, "csrf-transfer": 8084, "weak-login": 8085}
assert name in ports
base = f"http://127.0.0.1:{ports[name]}"
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def visit(path="/", fields=None):
    data = None if fields is None else urllib.parse.urlencode(fields).encode()
    with opener.open(base + path, data=data, timeout=15) as response:
        return response.read().decode()


if name == "broken-access-control":
    assert "N. Perera" in visit("/?account=1")
    assert "M. Fernando" in visit("/?account=2")
elif name == "sql-injection":
    assert "Colombo" in visit("/?q=Colombo")
    result = visit("/?q=" + urllib.parse.quote("' OR 1=1 -- "))
    assert all(city in result for city in ("Colombo", "Kandy", "Galle"))
elif name == "stored-xss":
    visit(fields={"message": "<img src=x onerror=alert(1)>"})
    assert "<img src=x onerror=alert(1)>" in visit()
elif name == "csrf-transfer":
    assert "LKR 1000" in visit()
    visit(fields={"payee": "Test", "amount": "100"})
    assert "LKR 900" in visit()
elif name == "weak-login":
    assert "No account exists" in visit(fields={"email": "unknown@example.test", "password": "wrong"})
    for _ in range(6):
        assert "Incorrect password" in visit(fields={"email": "n.perera@example.test", "password": "wrong"})

print(name + " scenario check passed")
