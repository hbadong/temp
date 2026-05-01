"""RBAC service tests."""

import uuid

import pytest
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.tenant import Tenant
from ai_employee.models.user import User
from ai_employee.schemas.rbac import PermissionCreate, RoleCreate, RoleUpdate
from ai_employee.services.rbac_service import RBACService


@pytest.fixture
async def test_tenant(db_session: AsyncSession) -> Tenant:
    """Create a test tenant."""
    t = Tenant(name="RBAC Test Tenant", code="rbac-test")
    db_session.add(t)
    await db_session.flush()
    await db_session.refresh(t)
    return t


@pytest.fixture
async def test_user(db_session: AsyncSession, test_tenant: Tenant) -> User:
    """Create a test user."""
    from ai_employee.core.security import hash_password

    u = User(
        email="rbac-user@example.com",
        username="rbacuser",
        hashed_password=hash_password("password"),
        tenant_id=str(test_tenant.id),
        is_active=True,
    )
    db_session.add(u)
    await db_session.flush()
    await db_session.refresh(u)
    return u


@pytest.fixture
def rbac_service(db_session: AsyncSession) -> RBACService:
    """Create RBAC service instance."""
    return RBACService(db_session)


# Permission tests

@pytest.mark.asyncio
async def test_create_permission(rbac_service: RBACService) -> None:
    """Test creating a permission."""
    data = PermissionCreate(code="test:create", description="Test create permission")
    perm = await rbac_service.create_permission(data)

    assert perm.code == "test:create"
    assert perm.description == "Test create permission"


@pytest.mark.asyncio
async def test_create_duplicate_permission(rbac_service: RBACService) -> None:
    """Test creating a duplicate permission."""
    data = PermissionCreate(code="test:dup", description="Duplicate")
    await rbac_service.create_permission(data)

    with pytest.raises(ConflictException):
        await rbac_service.create_permission(data)


@pytest.mark.asyncio
async def test_get_permission_by_code(rbac_service: RBACService) -> None:
    """Test getting permission by code."""
    data = PermissionCreate(code="test:get", description="Get")
    await rbac_service.create_permission(data)

    perm = await rbac_service.get_permission_by_code("test:get")
    assert perm is not None
    assert perm.code == "test:get"


@pytest.mark.asyncio
async def test_get_permission_by_code_not_found(rbac_service: RBACService) -> None:
    """Test getting non-existent permission."""
    perm = await rbac_service.get_permission_by_code("nonexistent")
    assert perm is None


@pytest.mark.asyncio
async def test_list_permissions(rbac_service: RBACService) -> None:
    """Test listing permissions."""
    await rbac_service.create_permission(PermissionCreate(code="list:perm:a", description="A"))
    await rbac_service.create_permission(PermissionCreate(code="list:perm:b", description="B"))

    perms = await rbac_service.list_permissions()
    assert len(perms) >= 2


@pytest.mark.asyncio
async def test_delete_permission(rbac_service: RBACService) -> None:
    """Test soft deleting a permission."""
    data = PermissionCreate(code="test:delete", description="Delete")
    perm = await rbac_service.create_permission(data)

    await rbac_service.delete_permission(perm.id)

    result = await rbac_service.get_permission_by_code("test:delete")
    assert result is None


# Role tests

@pytest.mark.asyncio
async def test_create_role(rbac_service: RBACService) -> None:
    """Test creating a role."""
    await rbac_service.create_permission(PermissionCreate(code="role:perm:test", description="Test"))

    data = RoleCreate(
        name="Test Role",
        description="A test role",
        permission_codes=["role:perm:test"],
    )
    role = await rbac_service.create_role(data)

    assert role.name == "Test Role"
    assert len(role.permissions) == 1


@pytest.mark.asyncio
async def test_create_duplicate_role(rbac_service: RBACService) -> None:
    """Test creating a duplicate role."""
    data = RoleCreate(name="Dup Role", description="Duplicate")
    await rbac_service.create_role(data)

    with pytest.raises(ConflictException):
        await rbac_service.create_role(data)


@pytest.mark.asyncio
async def test_get_role_by_id(rbac_service: RBACService) -> None:
    """Test getting role by ID."""
    data = RoleCreate(name="Get Role", description="Get test")
    role = await rbac_service.create_role(data)

    fetched = await rbac_service.get_role_by_id(role.id)
    assert fetched.name == "Get Role"


@pytest.mark.asyncio
async def test_get_role_by_id_not_found(rbac_service: RBACService) -> None:
    """Test getting non-existent role."""
    with pytest.raises(NotFoundException):
        await rbac_service.get_role_by_id(uuid.uuid4())


@pytest.mark.asyncio
async def test_list_roles(rbac_service: RBACService) -> None:
    """Test listing roles."""
    await rbac_service.create_role(RoleCreate(name="List Role A", description="A"))
    await rbac_service.create_role(RoleCreate(name="List Role B", description="B"))

    roles = await rbac_service.list_roles()
    assert len(roles) >= 2


@pytest.mark.asyncio
async def test_update_role(rbac_service: RBACService) -> None:
    """Test updating a role."""
    data = RoleCreate(name="Update Role", description="Before")
    role = await rbac_service.create_role(data)

    updated = await rbac_service.update_role(
        role.id,
        RoleUpdate(name="Updated Role", description="After"),
    )
    assert updated.name == "Updated Role"
    assert updated.description == "After"


@pytest.mark.asyncio
async def test_delete_role(rbac_service: RBACService) -> None:
    """Test soft deleting a role."""
    data = RoleCreate(name="Delete Role", description="Delete")
    role = await rbac_service.create_role(data)

    await rbac_service.delete_role(role.id)

    with pytest.raises(NotFoundException):
        await rbac_service.get_role_by_id(role.id)


# Permission assignment tests

@pytest.mark.asyncio
async def test_add_permissions_to_role(rbac_service: RBACService) -> None:
    """Test adding permissions to a role."""
    await rbac_service.create_permission(PermissionCreate(code="add:perm:test", description="Test"))
    data = RoleCreate(name="Add Perm Role", description="Test")
    role = await rbac_service.create_role(data)

    role = await rbac_service.add_permissions_to_role(role.id, ["add:perm:test"])
    codes = [p.code for p in role.permissions]
    assert "add:perm:test" in codes


@pytest.mark.asyncio
async def test_add_nonexistent_permission_to_role(rbac_service: RBACService) -> None:
    """Test adding a non-existent permission raises NotFoundException."""
    data = RoleCreate(name="Add Fail Role", description="Test")
    role = await rbac_service.create_role(data)

    with pytest.raises(NotFoundException):
        await rbac_service.add_permissions_to_role(role.id, ["nonexistent:perm"])


@pytest.mark.asyncio
async def test_remove_permissions_from_role(rbac_service: RBACService) -> None:
    """Test removing permissions from a role."""
    await rbac_service.create_permission(PermissionCreate(code="remove:perm:test", description="Test"))
    data = RoleCreate(
        name="Remove Perm Role",
        description="Test",
        permission_codes=["remove:perm:test"],
    )
    role = await rbac_service.create_role(data)

    role = await rbac_service.remove_permissions_from_role(role.id, ["remove:perm:test"])
    codes = [p.code for p in role.permissions]
    assert "remove:perm:test" not in codes


# User-Role assignment tests

@pytest.mark.asyncio
async def test_assign_role_to_user(rbac_service: RBACService, test_user: User) -> None:
    """Test assigning a role to a user."""
    data = RoleCreate(name="Assign Role", description="Test")
    role = await rbac_service.create_role(data)

    user_role = await rbac_service.assign_role_to_user(test_user.id, role.id)
    assert user_role.user_id == test_user.id
    assert user_role.role_id == role.id


@pytest.mark.asyncio
async def test_assign_duplicate_role_to_user(rbac_service: RBACService, test_user: User) -> None:
    """Test assigning a duplicate role raises ConflictException."""
    data = RoleCreate(name="Dup Assign Role", description="Test")
    role = await rbac_service.create_role(data)

    await rbac_service.assign_role_to_user(test_user.id, role.id)

    with pytest.raises(ConflictException):
        await rbac_service.assign_role_to_user(test_user.id, role.id)


@pytest.mark.asyncio
async def test_get_user_roles(rbac_service: RBACService, test_user: User) -> None:
    """Test getting user roles."""
    await rbac_service.create_permission(PermissionCreate(code="user:role:test", description="Test"))
    data = RoleCreate(
        name="User Role Test",
        description="Test",
        permission_codes=["user:role:test"],
    )
    role = await rbac_service.create_role(data)

    await rbac_service.assign_role_to_user(test_user.id, role.id)

    roles = await rbac_service.get_user_roles(test_user.id)
    assert len(roles) == 1
    assert roles[0].name == "User Role Test"


@pytest.mark.asyncio
async def test_get_user_permissions(rbac_service: RBACService, test_user: User) -> None:
    """Test getting user effective permissions."""
    await rbac_service.create_permission(PermissionCreate(code="user:perm:effective", description="Test"))
    data = RoleCreate(
        name="Effective Perm Role",
        description="Test",
        permission_codes=["user:perm:effective"],
    )
    role = await rbac_service.create_role(data)

    await rbac_service.assign_role_to_user(test_user.id, role.id)

    perms = await rbac_service.get_user_permissions(test_user.id)
    codes = [p.code for p in perms.permissions]
    assert "user:perm:effective" in codes


@pytest.mark.asyncio
async def test_has_permission(rbac_service: RBACService, test_user: User) -> None:
    """Test checking if user has a specific permission."""
    await rbac_service.create_permission(PermissionCreate(code="check:perm:test", description="Test"))
    data = RoleCreate(
        name="Check Perm Role",
        description="Test",
        permission_codes=["check:perm:test"],
    )
    role = await rbac_service.create_role(data)

    await rbac_service.assign_role_to_user(test_user.id, role.id)

    has_perm = await rbac_service.has_permission(test_user.id, "check:perm:test")
    assert has_perm is True

    no_perm = await rbac_service.has_permission(test_user.id, "nonexistent:perm")
    assert no_perm is False


@pytest.mark.asyncio
async def test_remove_role_from_user(rbac_service: RBACService, test_user: User) -> None:
    """Test removing a role from a user."""
    data = RoleCreate(name="Remove User Role", description="Test")
    role = await rbac_service.create_role(data)

    await rbac_service.assign_role_to_user(test_user.id, role.id)
    await rbac_service.remove_role_from_user(test_user.id, role.id)

    roles = await rbac_service.get_user_roles(test_user.id)
    assert len(roles) == 0


@pytest.mark.asyncio
async def test_remove_role_from_user_not_found(rbac_service: RBACService, test_user: User) -> None:
    """Test removing a non-existent role raises NotFoundException."""
    data = RoleCreate(name="Remove Fail Role", description="Test")
    role = await rbac_service.create_role(data)

    with pytest.raises(NotFoundException):
        await rbac_service.remove_role_from_user(test_user.id, role.id)
