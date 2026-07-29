.PHONY: install test test-unit test-integration \
        docker-up docker-down docker-logs

# ---------------------------------------------------------------------------
# Dependencies
# ---------------------------------------------------------------------------
install:
	composer install

# ---------------------------------------------------------------------------
# Unit tests (no Docker required — fast, run locally)
# ---------------------------------------------------------------------------
test-unit: install
	./vendor/bin/phpunit --configuration phpunit.xml

# ---------------------------------------------------------------------------
# Integration tests (spins up Docker, runs suite, tears down)
# ---------------------------------------------------------------------------
test-integration: install
	docker compose -f docker-compose.test.yml build test-runner
	docker compose -f docker-compose.test.yml run --rm test-runner
	$(MAKE) docker-down

# Run both suites
test: test-unit test-integration

# ---------------------------------------------------------------------------
# Docker helpers
# ---------------------------------------------------------------------------

# Start the DB + WordPress + run the setup script (useful for manual testing)
docker-up:
	docker compose -f docker-compose.test.yml up -d db wordpress
	docker compose -f docker-compose.test.yml run --rm setup

docker-down:
	docker compose -f docker-compose.test.yml down -v --remove-orphans

docker-logs:
	docker compose -f docker-compose.test.yml logs -f
