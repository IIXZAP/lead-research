from app.services.normalizer import normalize_company_name, normalize_domain, normalize_phone


def test_normalize_company_name_strips_legal_entity_words():
    assert normalize_company_name("บริษัท ตัวอย่าง จำกัด") == "ตัวอย่าง"


def test_normalize_company_name_collapses_whitespace_and_lowercases():
    assert normalize_company_name("  Example   Shop  ") == "example shop"


def test_normalize_company_name_does_not_mutate_display_value():
    display = "บริษัท ตัวอย่าง จำกัด"
    normalize_company_name(display)
    assert display == "บริษัท ตัวอย่าง จำกัด"


def test_normalize_phone_converts_plus66_to_leading_zero():
    assert normalize_phone("+66 81 234 5678") == "0812345678"


def test_normalize_phone_keeps_local_format_digits_only():
    assert normalize_phone("081-234-5678") == "0812345678"


def test_normalize_phone_returns_none_for_missing_value():
    assert normalize_phone(None) is None
    assert normalize_phone("") is None


def test_normalize_phone_never_pads_incomplete_numbers():
    assert normalize_phone("081-234") == "081234"


def test_normalize_domain_strips_protocol_www_and_trailing_slash():
    assert normalize_domain("HTTPS://WWW.Example.com/path/") == "example.com"


def test_normalize_domain_handles_bare_hostname():
    assert normalize_domain("example.com") == "example.com"


def test_normalize_domain_returns_none_for_missing_value():
    assert normalize_domain(None) is None
