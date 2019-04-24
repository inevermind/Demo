extern "C" {
	#include "lua.h"
	#include "lualib.h"
	#include "lauxlib.h"
	#include "luasocket.h"
}

void lua_register_function(lua_State *L, char const * const tableName, char const * const funcName, int (*function)(lua_State *L));
char *lua_get_traceback(lua_State *L);
int print_message(lua_State *L);

#define SHOW_PRINT 1