"""User service for business logic."""

import uuid

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.core.security import hash_password, verify_password
from ai_employee.models.user import User
from ai_employee.schemas.user import UserCreate, UserUpdate


class UserService:
    """Service for user CRUD operations."""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def get_by_id(self, user_id: uuid.UUID) -> User:
        """Get user by ID."""
        stmt = select(User).where(
            User.id == str(user_id),
            User.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        user = result.scalar_one_or_none()

        if not user:
            raise NotFoundException("User")

        return user

    async def get_by_email(self, email: str) -> User | None:
        """Get user by email."""
        stmt = select(User).where(
            User.email == email,
            User.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def get_by_username(self, username: str) -> User | None:
        """Get user by username."""
        stmt = select(User).where(
            User.username == username,
            User.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def list_users(
        self,
        tenant_id: uuid.UUID | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[User], int]:
        """List users with optional tenant filter and pagination."""
        where_conditions = [User.is_deleted == False]  # noqa: E712

        if tenant_id:
            where_conditions.append(User.tenant_id == str(tenant_id))

        count_stmt = select(func.count(User.id)).where(*where_conditions)
        count_result = await self.db.execute(count_stmt)
        total = count_result.scalar() or 0

        stmt = (
            select(User)
            .where(*where_conditions)
            .order_by(User.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all()), total

    async def create(self, data: UserCreate) -> User:
        """Create a new user."""
        if await self.get_by_email(data.email):
            raise ConflictException("Email already registered")

        if await self.get_by_username(data.username):
            raise ConflictException("Username already taken")

        user_data = data.model_dump()
        user_data["hashed_password"] = hash_password(user_data.pop("password"))
        user_data["tenant_id"] = str(user_data["tenant_id"])

        user = User(**user_data)
        self.db.add(user)
        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def update(self, user_id: uuid.UUID, data: UserUpdate) -> User:
        """Update an existing user."""
        user = await self.get_by_id(user_id)

        update_data = data.model_dump(exclude_unset=True)

        if "email" in update_data:
            existing = await self.get_by_email(update_data["email"])
            if existing and existing.id != str(user_id):
                raise ConflictException("Email already registered")

        if "username" in update_data:
            existing = await self.get_by_username(update_data["username"])
            if existing and existing.id != str(user_id):
                raise ConflictException("Username already taken")

        if "password" in update_data:
            update_data["hashed_password"] = hash_password(update_data.pop("password"))

        for key, value in update_data.items():
            setattr(user, key, value)

        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def update_profile(
        self,
        user_id: uuid.UUID,
        email: str | None = None,
        username: str | None = None,
        full_name: str | None = None,
    ) -> User:
        """Update user profile (self-service)."""
        user = await self.get_by_id(user_id)

        if email is not None:
            existing = await self.get_by_email(email)
            if existing and existing.id != str(user_id):
                raise ConflictException("Email already registered")
            user.email = email

        if username is not None:
            existing = await self.get_by_username(username)
            if existing and existing.id != str(user_id):
                raise ConflictException("Username already taken")
            user.username = username

        if full_name is not None:
            user.full_name = full_name

        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def change_password(
        self,
        user_id: uuid.UUID,
        current_password: str,
        new_password: str,
    ) -> None:
        """Change user password with current password verification."""
        user = await self.get_by_id(user_id)

        if not verify_password(current_password, user.hashed_password):
            raise ConflictException("Current password is incorrect")

        user.hashed_password = hash_password(new_password)
        await self.db.flush()

    async def delete(self, user_id: uuid.UUID) -> None:
        """Soft delete a user."""
        user = await self.get_by_id(user_id)
        user.soft_delete()
        await self.db.flush()

    async def authenticate(self, email: str, password: str) -> User | None:
        """Authenticate user with email and password."""
        user = await self.get_by_email(email)
        if not user:
            return None

        if not verify_password(password, user.hashed_password):
            return None

        if not user.is_active:
            return None

        return user
