from app.schemas.audit import AuditMetrics
from app.services.issue_classifier import build_summary, classify


def test_no_website_yields_website_not_found():
    issues = classify(AuditMetrics(website_url=None))

    assert [i.code for i in issues] == ["WEBSITE_NOT_FOUND"]
    assert issues[0].message_th == "ไม่พบเว็บไซต์ของบริษัท"


def test_dns_error_is_classified():
    issues = classify(AuditMetrics(website_url="https://x.com", dns_resolved=False))

    assert issues[0].code == "DNS_ERROR"


def test_slow_response_uses_configured_threshold_and_seconds_message():
    issues = classify(
        AuditMetrics(website_url="https://x.com", http_status=200, response_time_ms=4200),
        slow_threshold_ms=3000,
    )

    slow = next(i for i in issues if i.code == "SLOW_RESPONSE")
    assert slow.message_th == "เว็บไซต์ตอบสนองช้า ใช้เวลา 4.2 วินาที"
    assert slow.evidence["threshold_ms"] == 3000


def test_https_not_enabled_is_flagged():
    issues = classify(AuditMetrics(website_url="http://x.com", http_status=200, https_enabled=False))

    assert any(i.code == "HTTPS_NOT_ENABLED" for i in issues)


def test_viewport_missing_is_capped_at_medium_confidence():
    issues = classify(
        AuditMetrics(website_url="https://x.com", http_status=200, mobile_viewport_found=False)
    )

    viewport_issue = next(i for i in issues if i.code == "MOBILE_VIEWPORT_MISSING")
    assert viewport_issue.confidence == "medium"


def test_no_issues_when_everything_is_fine():
    metrics = AuditMetrics(
        website_url="https://x.com",
        http_status=200,
        https_enabled=True,
        ssl_valid=True,
        response_time_ms=500,
        mobile_viewport_found=True,
        title_found=True,
        meta_description_found=True,
        contact_information_found=True,
        contact_form_found=True,
        broken_link_count=0,
        mixed_content_found=False,
    )

    issues = classify(metrics)

    assert issues == []
    assert build_summary(issues) == "ยังไม่พบปัญหาที่ชัดเจนจากการตรวจสอบเบื้องต้น"


def test_summary_never_claims_perfection():
    summary = build_summary([])
    assert "สมบูรณ์แบบ" not in summary
    assert "ไม่มีปัญหา" not in summary
