"""Custom exceptions for the application."""

from fastapi import HTTPException, status


class NotFoundException(HTTPException):
    """Raised when a resource is not found."""

    def __init__(self, resource: str = "Resource", detail: str | None = None) -> None:
        message = detail or f"{resource} not found"
        super().__init__(status_code=status.HTTP_404_NOT_FOUND, detail=message)


class UnauthorizedException(HTTPException):
    """Raised when authentication fails."""

    def __init__(self, detail: str = "Authentication required") -> None:
        super().__init__(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=detail,
            headers={"WWW-Authenticate": "Bearer"},
        )


class ForbiddenException(HTTPException):
    """Raised when user lacks permission."""

    def __init__(self, detail: str = "Access forbidden") -> None:
        super().__init__(status_code=status.HTTP_403_FORBIDDEN, detail=detail)


class ConflictException(HTTPException):
    """Raised when a resource already exists."""

    def __init__(self, detail: str = "Resource already exists") -> None:
        super().__init__(status_code=status.HTTP_409_CONFLICT, detail=detail)


class ValidationException(HTTPException):
    """Raised when input validation fails."""

    def __init__(self, detail: str = "Validation failed") -> None:
        super().__init__(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail=detail)
