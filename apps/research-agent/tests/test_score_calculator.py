from app.schemas.audit import Issue
from app.services.score_calculator import compute_confidence, compute_score


def test_no_issues_scores_100():
    assert compute_score([]) == 100


def test_website_not_found_scores_zero():
    assert compute_score(["WEBSITE_NOT_FOUND"]) == 0


def test_score_never_goes_below_zero():
    assert compute_score(["WEBSITE_NOT_FOUND", "DNS_ERROR", "HTTP_SERVER_ERROR"]) == 0


def test_weights_are_configurable():
    custom_weights = {"SLOW_RESPONSE": 50}
    assert compute_score(["SLOW_RESPONSE"], weights=custom_weights) == 50


def test_unknown_issue_code_costs_nothing():
    assert compute_score(["SOME_UNKNOWN_CODE"]) == 100


def test_confidence_is_full_when_no_issues():
    assert compute_confidence([]) == 1.0


def test_confidence_averages_issue_confidence_levels():
    issues = [
        Issue(code="A", severity="low", confidence="high", message_th="x"),
        Issue(code="B", severity="low", confidence="low", message_th="x"),
    ]

    assert compute_confidence(issues) == 0.65
