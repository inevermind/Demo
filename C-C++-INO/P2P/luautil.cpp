#include "luautil.h"

void lua_register_function(lua_State *L, char const * const tableName, char const * const funcName, int (*function)(lua_State *L)) {
    lua_getglobal(L, tableName);
    if (!lua_istable(L, -1)) {
        lua_createtable(L, 0, 1);
        lua_setglobal(L, tableName);
        lua_pop(L, 1);
        lua_getglobal(L, tableName);
    }

    lua_pushstring(L, funcName);
    lua_pushcfunction(L, function);
    lua_settable(L, -3);
    lua_pop(L, 1);
}

char *lua_get_traceback(lua_State *L) {
	char *traceback;

	if (!lua_isstring(L, 1)) {
		return NULL;
	}

	lua_getglobal(L, "debug");
	if (!lua_istable(L, -1)) {
		lua_pop(L, 1);
		return NULL;
	}
	
	lua_getfield(L, -1, "traceback");
	if (!lua_isfunction(L, -1)) {
		lua_pop(L, 2);
		return NULL;
	}
  
	lua_pushvalue(L, 1);
	lua_pushinteger(L, 2);
	lua_call(L, 2, 1);
	int iType = lua_type(L, -1);
	if (iType == LUA_TSTRING) {
		const char *pValue = lua_tostring(L, -1);
		traceback = const_cast<char*>(pValue);
	}

	return traceback;
}

int print_message(lua_State *L) {
	if (SHOW_PRINT) {
		const char *message = luaL_checkstring(L, 1);
		printf(message);
	}

	return 0;
}