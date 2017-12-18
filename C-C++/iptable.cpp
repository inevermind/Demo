#include <winsock2.h>
#include <ws2tcpip.h>
#include <iphlpapi.h>
#include <stdio.h>

#include "iptable.h"
#include "luautil.h"

static int getIPList(lua_State *L) {
    int i;
    PMIB_IPADDRTABLE pIPAddrTable;
    DWORD dwSize = 0;
    DWORD dwRetVal = 0;
    IN_ADDR IPAddr;
	int ipCount = 0;

	lua_newtable(L);

    pIPAddrTable = (MIB_IPADDRTABLE *) MALLOC(sizeof (MIB_IPADDRTABLE));
    if (pIPAddrTable) {
        if (GetIpAddrTable(pIPAddrTable, &dwSize, 0) == ERROR_INSUFFICIENT_BUFFER) {
            FREE(pIPAddrTable);
            pIPAddrTable = (MIB_IPADDRTABLE *) MALLOC(dwSize);
        }
        if (pIPAddrTable == NULL) {
            return 1;
        }
    }
    if ((dwRetVal = GetIpAddrTable( pIPAddrTable, &dwSize, 0 )) != NO_ERROR ) { 
        return 1;
    }

    for (i=0; i < (int)pIPAddrTable->dwNumEntries; i++) {
		if (
			pIPAddrTable->table[i].wType & MIB_IPADDR_DISCONNECTED ||
			pIPAddrTable->table[i].wType & MIB_IPADDR_DELETED ||
			pIPAddrTable->table[i].wType & MIB_IPADDR_TRANSIENT
		) {
			continue;
		}

		IPAddr.S_un.S_addr = (u_long) pIPAddrTable->table[i].dwAddr;
		lua_pushstring(L, inet_ntoa(IPAddr));
		ipCount++;
		lua_rawseti(L, -2, ipCount);
    }

    if (pIPAddrTable) {
        FREE(pIPAddrTable);
        pIPAddrTable = NULL;
    }

	return 1;
}

int iptable_init(lua_State *L) {
	lua_register_function(L, "iptable", "getlist", getIPList);

	return 1;
}