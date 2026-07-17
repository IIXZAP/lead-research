import httpx
import respx

from app.tasks import campaign_tasks


def test_process_campaign_runs_end_to_end_and_sends_callbacks(monkeypatch, fake_redis):
    monkeypatch.setattr(campaign_tasks, "_redis_client", lambda: fake_redis)
    monkeypatch.setattr("app.services.website_auditor.assert_safe_url", lambda url: None)

    callback_calls = []

    def callback_handler(request: httpx.Request) -> httpx.Response:
        callback_calls.append(request.content)
        return httpx.Response(200, json={"ok": True})

    with respx.mock(assert_all_called=False) as router:
        router.post("https://laravel.test/callback").mock(side_effect=callback_handler)

        # Every mock business website resolves to a generic 200 page so the
        # audit stage has something deterministic to measure, regardless
        # of which of the ~15 mock companies get selected.
        router.get(url__regex=r"https://.*\.example\.com").mock(
            return_value=httpx.Response(
                200,
                html="<html><head><title>x</title><meta name='viewport' content='w'></head><body>02-000-0000</body></html>",
            )
        )
        router.head(url__regex=r"https://.*\.example\.com.*").mock(return_value=httpx.Response(200))

        result = campaign_tasks.process_campaign.run(
            job_id="job-integration-1",
            campaign_id=42,
            criteria={"business_keyword": "test", "maximum_leads": 5},
            callback_url="https://laravel.test/callback",
        )

    assert result["status"] in {"completed", "partially_completed"}
    assert len(callback_calls) >= 1

    final_state = campaign_tasks.JobStore(fake_redis).get("job-integration-1")
    assert final_state["status"] == result["status"]
    assert final_state["progress_percent"] == 100


def test_process_campaign_reports_failed_status_on_provider_error(monkeypatch, fake_redis):
    monkeypatch.setattr(campaign_tasks, "_redis_client", lambda: fake_redis)

    def broken_provider(*args, **kwargs):
        raise RuntimeError("provider exploded")

    monkeypatch.setattr(
        "app.tasks.search_tasks.generate_search_queries.run",
        lambda criteria: (_ for _ in ()).throw(RuntimeError("provider exploded")),
    )

    callback_calls = []

    def callback_handler(request: httpx.Request) -> httpx.Response:
        callback_calls.append(request.content)
        return httpx.Response(200, json={"ok": True})

    with respx.mock(assert_all_called=False) as router:
        router.post("https://laravel.test/callback").mock(side_effect=callback_handler)

        try:
            campaign_tasks.process_campaign.run(
                job_id="job-integration-2",
                campaign_id=42,
                criteria={"business_keyword": "test", "maximum_leads": 5},
                callback_url="https://laravel.test/callback",
            )
        except RuntimeError:
            pass

    final_state = campaign_tasks.JobStore(fake_redis).get("job-integration-2")
    assert final_state["status"] == "failed"
    assert len(callback_calls) == 1
