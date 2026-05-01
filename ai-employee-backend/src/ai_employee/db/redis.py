"""Redis connection management."""

import redis.asyncio as aioredis

from ai_employee.config import settings

redis_client: aioredis.Redis | None = None


async def init_redis() -> None:
    """Initialize Redis connection pool."""
    global redis_client

    redis_client = aioredis.from_url(
        str(settings.redis_url),
        encoding="utf-8",
        decode_responses=True,
        max_connections=20,
    )

    # Test connection
    await redis_client.ping()


async def close_redis() -> None:
    """Close Redis connection."""
    global redis_client
    if redis_client:
        await redis_client.close()


def get_redis() -> aioredis.Redis:
    """Get Redis client instance."""
    if redis_client is None:
        raise RuntimeError("Redis not initialized. Call init_redis() first.")
    return redis_client
