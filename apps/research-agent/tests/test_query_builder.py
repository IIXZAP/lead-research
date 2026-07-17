from app.schemas.business import SearchCriteria
from app.services.query_builder import build_queries, deduplicate_queries


def test_build_queries_includes_keyword_and_province():
    criteria = SearchCriteria(business_keyword="โรงงานผลิตอาหาร", province="สมุทรปราการ")

    queries = build_queries(criteria)

    assert "โรงงานผลิตอาหาร สมุทรปราการ" in queries


def test_build_queries_includes_english_variant_when_known():
    criteria = SearchCriteria(business_keyword="โรงงานผลิตอาหาร", province="สมุทรปราการ")

    queries = build_queries(criteria)

    assert any("food manufacturing company" in q for q in queries)


def test_build_queries_never_repeats_a_query_within_a_campaign():
    queries = deduplicate_queries(["a", "A", " a ", "b"])

    assert queries == ["a", "b"]


def test_build_queries_skips_english_variant_for_unknown_keyword():
    criteria = SearchCriteria(business_keyword="ธุรกิจแปลกใหม่", province="เชียงใหม่")

    queries = build_queries(criteria)

    assert all("company" not in q for q in queries)
