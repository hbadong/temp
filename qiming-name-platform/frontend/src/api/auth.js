import request from '../utils/request';

export function login(data) {
  return request.post('/v1/auth/login', data);
}

export function register(data) {
  return request.post('/v1/auth/register', data);
}

export function logout() {
  return request.post('/v1/auth/logout');
}

export function getUserInfo() {
  return request.get('/v1/auth/userinfo');
}

export function updateUserInfo(data) {
  return request.put('/v1/auth/userinfo', data);
}
