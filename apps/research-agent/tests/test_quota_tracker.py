import pytest

from app.services.quota_tracker import QuotaExceededError, QuotaTracker


def test_remaining_starts_at_max(fake_redis):
    tracker = QuotaTracker(fake_redis, campaign_id=1, max_requests=5)

    assert tracker.remaining() == 5


def test_spend_decrements_remaining(fake_redis):
    tracker = QuotaTracker(fake_redis, campaign_id=1, max_requests=5)
    tracker.spend(2)

    assert tracker.remaining() == 3


def test_spend_raises_when_quota_exhausted(fake_redis):
    tracker = QuotaTracker(fake_redis, campaign_id=1, max_requests=2)
    tracker.spend(2)

    with pytest.raises(QuotaExceededError):
        tracker.spend(1)


def test_quota_is_scoped_per_campaign(fake_redis):
    tracker_a = QuotaTracker(fake_redis, campaign_id=1, max_requests=5)
    tracker_b = QuotaTracker(fake_redis, campaign_id=2, max_requests=5)

    tracker_a.spend(5)

    assert tracker_a.remaining() == 0
    assert tracker_b.remaining() == 5
