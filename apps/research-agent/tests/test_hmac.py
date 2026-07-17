import time

from app.security.hmac import sign, verify


def test_verify_accepts_a_valid_signature():
    ts = str(int(time.time()))
    body = '{"a":1}'
    signature = sign("secret", ts, body)

    assert verify("secret", ts, body, signature, ttl_seconds=300)


def test_verify_rejects_wrong_secret():
    ts = str(int(time.time()))
    body = '{"a":1}'
    signature = sign("secret", ts, body)

    assert not verify("other-secret", ts, body, signature, ttl_seconds=300)


def test_verify_rejects_tampered_body():
    ts = str(int(time.time()))
    signature = sign("secret", ts, '{"a":1}')

    assert not verify("secret", ts, '{"a":2}', signature, ttl_seconds=300)


def test_verify_rejects_expired_timestamp():
    old_ts = str(int(time.time()) - 400)
    body = '{"a":1}'
    signature = sign("secret", old_ts, body)

    assert not verify("secret", old_ts, body, signature, ttl_seconds=300)


def test_verify_rejects_malformed_timestamp():
    assert not verify("secret", "not-a-number", "{}", "deadbeef", ttl_seconds=300)
