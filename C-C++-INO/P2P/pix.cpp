#include <stdio.h>
#include <windows.h>
#include <ole2.h>

#include "luautil.h"
#include "luacom.h"
#include "iptable.h"

extern "C" {
	#include "lptree.h"
}


int main(int argc, char *argv[]) {
    CoInitialize(NULL);
    lua_State *L;
    L = luaL_newstate();
	luaL_openlibs(L);
	luaL_requiref(L, "socket.core", luaopen_socket_core, 0);
	luaL_requiref(L, "iptable", iptable_init, 0);
	luaL_requiref(L, "lpeg", luaopen_lpeg, 0);
	luacom_open(L);
	lua_register_function(L, "console", "log", print_message);
 
	if (luaL_loadfile(L, "main.lua") == 0) {
		if (lua_pcall(L, 0, LUA_MULTRET, 0) != 0) {
			printf("Error: %s\n", lua_tostring(L, -1));
			printf("Traceback: %s\n", lua_get_traceback(L));
		}
	}

	luacom_close(L);
	lua_close (L);
	CoUninitialize();

	return 0;
}