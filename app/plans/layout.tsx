"use client";

import { useState, useEffect } from "react";
import { VaultLogo } from "@/components/vault-logo";
import { User, Settings, Users, BarChart2, FileText, Layers, TrendingUp, ArrowLeft } from "lucide-react";
import Link from "next/link";

const sidebarLinks = [
  { label: "Dashboard", icon: BarChart2, href: "/admin-dashboard" },
  { label: "Deposits", icon: TrendingUp, children: [
    { label: "Pending Deposits", href: "/admin-dashboard/deposits/pending" },
    { label: "Approved Deposits", href: "/admin-dashboard/deposits/approved" },
    { label: "All Deposits", href: "/admin-dashboard/deposits/all" },
  ]},
  { label: "Withdrawals", icon: Layers, children: [
    { label: "Pending Withdrawals", href: "/admin-dashboard/withdrawals/pending" },
    { label: "Approved Withdrawals", href: "/admin-dashboard/withdrawals/approved" },
    { label: "All Withdrawals", href: "/admin-dashboard/withdrawals/all" },
  ]},
  { label: "Users", icon: Users, children: [
    { label: "Active Users", href: "/admin-dashboard/admin-users" },
    { label: "All Users", href: "/admin-dashboard/admin-users" },
  ]},
  { label: "Staking", icon: FileText, children: [
    { label: "Plan Management", href: "/admin-dashboard/plans" },
    { label: "Staking Invest", href: "/admin-dashboard/staking-invest" },
    { label: "Statistics", href: "/admin-dashboard/statistics" },
  ]},
  { label: "Reports", icon: BarChart2, children: [
    { label: "Deposit & Withdrawal Report", href: "/admin-dashboard/reports/deposit-withdrawal" },
    { label: "Transaction Report", href: "/admin-dashboard/reports/transactions" },
  ]},
  { label: "System Settings", icon: Settings, href: "/admin-dashboard/settings", superAdmin: true },
];

export default function PlansLayout({ children }: { children: React.ReactNode }) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [adminName, setAdminName] = useState("Admin");
  const isSuperAdmin = true;

  useEffect(() => {
    if (typeof window !== "undefined") {
      const session = localStorage.getItem("vault_user");
      if (session) {
        try {
          const user = JSON.parse(session);
          setAdminName(user.firstName || "Admin");
        } catch {}
      }
    }
  }, []);

  return (
    <div className="min-h-screen bg-black text-white flex">
      {/* Sidebar */}
      <aside className={`fixed z-30 top-0 left-0 h-full w-64 bg-gray-900 border-r border-gray-800/50 transition-transform duration-300 ${sidebarOpen ? "translate-x-0" : "-translate-x-64"} md:translate-x-0 md:static md:block`}>
        <div className="flex items-center justify-between px-6 py-6 border-b border-gray-800/50">
          <VaultLogo width={160} height={40} className="h-10 w-auto" />
          <button className="md:hidden text-gray-400" onClick={() => setSidebarOpen(false)}>
            <ArrowLeft className="w-5 h-5" />
          </button>
        </div>
        <nav className="mt-6 space-y-2 px-4">
          {sidebarLinks.map((item) => (
            (!item.superAdmin || isSuperAdmin) && (
              <div key={item.label}>
                <Link href={item.href || "#"} className="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-800/70 transition-colors font-medium">
                  <item.icon className="w-5 h-5 text-blue-400" />
                  <span>{item.label}</span>
                </Link>
                {item.children && (
                  <div className="ml-8 mt-1 space-y-1">
                    {item.children.map((child) => (
                      <Link key={child.label} href={child.href} className="block px-2 py-1 rounded hover:bg-gray-800/50 text-gray-300 text-sm transition-colors">
                        {child.label}
                      </Link>
                    ))}
                  </div>
                )}
              </div>
            )
          ))}
        </nav>
      </aside>
      {/* Main Content */}
      <div className="flex-1 flex flex-col min-h-screen ml-0 md:ml-64">
        {/* Header */}
        <header className="flex items-center justify-between px-6 py-6 border-b border-gray-800/50 bg-black/80 backdrop-blur-xl sticky top-0 z-20">
          <div className="md:hidden">
            <button onClick={() => setSidebarOpen(true)} className="text-gray-400">
              <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="feather feather-menu"><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="18" x2="21" y2="18" /></svg>
            </button>
          </div>
          <div />
          <div className="flex items-center space-x-4">
            <div className="relative group">
              <button className="flex items-center space-x-2 focus:outline-none">
                <User className="w-7 h-7 text-white" />
                <span className="hidden md:inline text-white font-medium">{adminName}</span>
              </button>
              <div className="absolute right-0 mt-2 w-48 bg-gray-900 border border-gray-800 rounded-lg shadow-lg py-2 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-all duration-200 z-50">
                <Link href="#" className="block px-4 py-2 text-gray-300 hover:bg-gray-800">Profile</Link>
                <Link href="#" className="block px-4 py-2 text-gray-300 hover:bg-gray-800">Change Password</Link>
                <Link href="/admin-login" className="block px-4 py-2 text-red-400 hover:bg-gray-800">Logout</Link>
              </div>
            </div>
          </div>
        </header>
        {/* Main Content Area */}
        <main className="flex-1 bg-black/90">
          <div className="px-4 py-8 w-full md:w-[1000px] md:mx-auto h-full md:px-5 md:ml-[-250px]">
            {children}
          </div>
        </main>
        {/* Footer */}
        <footer className="px-6 py-4 border-t border-gray-800/50 bg-black/80 text-gray-400 text-sm text-center">
          &copy; {new Date().getFullYear()} Vault Admin Dashboard. All rights reserved.
        </footer>
      </div>
    </div>
  );
} 