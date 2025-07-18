"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { motion, AnimatePresence } from "framer-motion"
import {
  Home,
  User,
  Layers,
  Download,
  Upload,
  List,
  Users,
  Settings,
  HelpCircle,
  LogOut,
  X,
} from "lucide-react"
import { VaultLogo } from "@/components/vault-logo"
import { useRouter } from "next/navigation"

const navLinks = [
  { href: "/dashboard", label: "Dashboard", icon: Home },
  { href: "/dashboard/profile", label: "Profile", icon: User },
  { href: "/dashboard/plans", label: "Plans", icon: Layers },
  { href: "/dashboard/deposits", label: "Deposits", icon: Download },
  { href: "/dashboard/withdrawals", label: "Withdrawals", icon: Upload },
  { href: "/dashboard/transactions", label: "Transactions", icon: List },
  { href: "/dashboard/referral", label: "Referral", icon: Users },
  { href: "/dashboard/settings", label: "Settings", icon: Settings },
  { href: "/dashboard/support", label: "Support", icon: HelpCircle },
]

export default function UserSidebar({ 
  open, 
  setOpen, 
  onNavigationClick 
}: { 
  open?: boolean, 
  setOpen?: (open: boolean) => void,
  onNavigationClick?: () => void 
}) {
  const pathname = usePathname()
  const router = useRouter()
  
  // Handle navigation click
  const handleNavClick = () => {
    if (onNavigationClick) {
      onNavigationClick()
    }
  }
  const handleLogout = () => {
    localStorage.removeItem("vault_user")
    router.push("/signin")
  }
  
  return (
    <div className="bg-[#0a101e]/95 backdrop-blur-xl text-white border-r border-gray-800/50 min-h-screen w-64 flex flex-col py-8 px-4 z-50">
      {/* Mobile Header */}
      {setOpen && (
        <motion.div 
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
          className="flex items-center justify-between mb-8 lg:hidden"
        >
          <VaultLogo width={140} height={40} className="h-8 w-auto" />
          <motion.button
            onClick={() => setOpen(false)}
            className="p-2 rounded-md hover:bg-gray-800/50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
          >
            <X className="w-5 h-5" />
          </motion.button>
        </motion.div>
      )}
      
      {/* Mobile Indicator */}
      {setOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="mb-4 p-2 bg-blue-600/10 border border-blue-500/20 rounded-lg lg:hidden"
        >
          <p className="text-xs text-blue-400 text-center">Mobile Navigation Active</p>
        </motion.div>
      )}

      {/* Desktop Logo */}
      <div className="hidden lg:block mb-8">
        <VaultLogo width={140} height={40} className="h-8 w-auto" />
      </div>

      {/* Navigation Links */}
      <nav className="flex-1 flex flex-col gap-2">
        <AnimatePresence>
          {navLinks.map(({ href, label, icon: Icon }, index) => {
            const isActive = pathname === href
            return (
              <motion.div
                key={href}
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ 
                  duration: 0.3, 
                  delay: index * 0.05,
                  ease: "easeOut"
                }}
              >
                <button
                  type="button"
                  onClick={() => {
                    if (pathname !== href) {
                      router.push(href)
                    }
                    if (onNavigationClick) {
                      onNavigationClick()
                    }
                  }}
                  className={`w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 font-medium text-base group relative overflow-hidden ${
                    isActive 
                      ? "bg-gradient-to-r from-blue-600/20 to-blue-500/20 text-blue-400 border border-blue-500/30 shadow-lg shadow-blue-500/10" 
                      : "text-gray-300 hover:bg-gray-800/50 hover:text-white"
                  }`}
                >
                  {/* Active indicator */}
                  {isActive && (
                    <motion.div
                      layoutId="activeTab"
                      className="absolute inset-0 bg-gradient-to-r from-blue-600/10 to-blue-500/10 border border-blue-500/20 rounded-lg"
                      initial={false}
                      transition={{ type: "spring", stiffness: 500, damping: 30 }}
                    />
                  )}
                  <Icon className={`w-5 h-5 relative z-10 ${isActive ? 'text-blue-400' : 'text-gray-400 group-hover:text-white'}`} />
                  <span className="relative z-10 font-medium">{label}</span>
                  {/* Hover effect */}
                  <motion.div
                    className="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-blue-500/5 rounded-lg"
                    initial={{ opacity: 0 }}
                    whileHover={{ opacity: 1 }}
                    transition={{ duration: 0.2 }}
                  />
                </button>
              </motion.div>
            )
          })}
        </AnimatePresence>
      </nav>
      
      {/* Logout Section */}
      <motion.div 
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.3, delay: 0.2 }}
        className="mt-auto pt-8 border-t border-gray-800/50"
      >
        <button
          type="button"
          onClick={handleLogout}
          className="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 font-medium text-base hover:bg-red-900/20 hover:text-red-400 text-red-400 group w-full"
        >
          <LogOut className="w-5 h-5" />
          <span>Logout</span>
        </button>
      </motion.div>
    </div>
  )
} 