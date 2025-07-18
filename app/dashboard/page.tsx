"use client"

import { useState, useEffect, useRef } from "react"
import { motion, AnimatePresence, easeInOut } from "framer-motion"
import { Button } from "@/components/ui/button"
import Link from "next/link"
import { useRouter } from "next/navigation"
import {
  ArrowLeft,
  Menu,
  X,
} from "lucide-react"
import { VaultLogo } from "@/components/vault-logo"
import { WalletConnection } from "@/components/wallet-connection"
import { MorphingBackground } from "@/components/morphing-background"
import { FloatingParticles } from "@/components/floating-particles"
import { DepositModal } from "@/components/deposit-modal"
import { WithdrawModal } from "@/components/withdraw-modal"
import { StakingPositions } from "@/components/staking-positions"
import { TransactionHistory } from "@/components/transaction-history"
import { PortfolioChart } from "@/components/portfolio-chart"
import UserSidebar from "@/components/user-sidebar"
import { DashboardWelcomeSection } from "@/components/dashboard-welcome-section"
import { PortfolioOverviewCards } from "@/components/portfolio-overview-cards"
import { QuickActionsCard } from "@/components/quick-actions-card"
import { DashboardLoading } from "@/components/dashboard-loading"
import { Progress } from "@/components/ui/progress"

export default function Dashboard() {
  const router = useRouter()
  const [isLoading, setIsLoading] = useState(true)
  const [showBalance, setShowBalance] = useState(true)
  const [showDepositModal, setShowDepositModal] = useState(false)
  const [showWithdrawModal, setShowWithdrawModal] = useState(false)
  const [userData, setUserData] = useState({
    totalBalance: 2847.65,
    stakedAmount: 1250.75,
    availableBalance: 1596.9,
    totalRewards: 156.32,
    portfolioChange: 12.5,
    apy: 12.8,
  })
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const sidebarButtonRef = useRef(null)
  const [user, setUser] = useState<{ firstName?: string; lastName?: string; email?: string } | null>(null)
  const [userLoading, setUserLoading] = useState(true)
  const [showWelcome, setShowWelcome] = useState(true)
  const [progress, setProgress] = useState(0)

  // Function to handle sidebar toggle
  const toggleSidebar = () => {
    console.log('Toggle sidebar clicked, current state:', sidebarOpen)
    setSidebarOpen(!sidebarOpen)
  }

  // Function to close sidebar when navigation item is clicked
  const handleNavigationClick = () => {
    console.log('Navigation clicked, closing sidebar')
    if (window.innerWidth < 1024) { // lg breakpoint
      setSidebarOpen(false)
    }
  }

  useEffect(() => {
    // Check if user is logged in and get user data
    const userString = typeof window !== "undefined" ? localStorage.getItem("vault_user") : null
    if (!userString) {
      router.push("/signin")
      return
    }

    try {
      const userData = JSON.parse(userString)
      setUser(userData)
    } catch (error) {
      console.error('Error parsing user data:', error)
      // If user data is not JSON, treat it as a simple string (email)
      const email = userString
      const firstName = email.split('@')[0] // Extract name from email
      setUser({ email, firstName })
    } finally {
      setUserLoading(false)
    }
  }, [router])

  useEffect(() => {
    // Progressive bar for welcome
    if (showWelcome) {
      setProgress(0)
      const interval = setInterval(() => {
        setProgress((old) => {
          if (old >= 100) {
            clearInterval(interval)
            setTimeout(() => setShowWelcome(false), 300)
            return 100
          }
          return old + 5
        })
      }, 40)
      return () => clearInterval(interval)
    }
  }, [showWelcome])

  // Function to get display name
  const getDisplayName = () => {
    if (userLoading) {
      return "..."
    }
    if (user?.firstName) {
      return user.firstName
    }
    if (user?.email) {
      // Extract name from email if no firstName
      return user.email.split('@')[0]
    }
    return "Investor" // Fallback
  }

  useEffect(() => {
    // Simulate loading
    const timer = setTimeout(() => {
      setIsLoading(false)
    }, 2000)

    return () => clearTimeout(timer)
  }, [])

  // Close sidebar on escape key
  useEffect(() => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape' && sidebarOpen) {
        setSidebarOpen(false)
      }
    }

    document.addEventListener('keydown', handleEscape)
    return () => document.removeEventListener('keydown', handleEscape)
  }, [sidebarOpen])

  // Close sidebar on window resize to desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 1024 && sidebarOpen) {
        setSidebarOpen(false)
      }
    }

    window.addEventListener('resize', handleResize)
    return () => window.removeEventListener('resize', handleResize)
  }, [sidebarOpen])

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.2,
      },
    },
  }

  const itemVariants = {
    hidden: { opacity: 0, y: 30 },
    visible: {
      opacity: 1,
      y: 0,
      transition: {
        duration: 0.6,
        ease: easeInOut,
      },
    },
  }

  if (showWelcome && user && !userLoading) {
    const fullName = (user.firstName && user.lastName)
      ? `${user.firstName} ${user.lastName}`
      : user.firstName || user.email?.split('@')[0] || "Investor"
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-black text-white">
        <div className="w-full max-w-md p-8">
          <Progress value={progress} />
          <div className="mt-8 text-center">
            <h2 className="text-2xl font-bold mb-2">Welcome {fullName} to your dashboard</h2>
            <p className="text-lg text-blue-400">Enjoy staking</p>
          </div>
        </div>
      </div>
    )
  }

  if (isLoading) {
    return <DashboardLoading />
  }

  return (
    <div className="min-h-screen bg-black text-white flex relative">
      {/* Mobile Sidebar Overlay */}
      <AnimatePresence>
        {sidebarOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
            onClick={() => setSidebarOpen(false)}
          />
        )}
      </AnimatePresence>

      {/* Sidebar - ensure this is rendered after the overlay and has z-50 */}
      <AnimatePresence>
        {sidebarOpen && (
          <motion.div
            initial={{ x: -320, opacity: 0 }}
            animate={{ x: 0, opacity: 1 }}
            exit={{ x: -320, opacity: 0 }}
            transition={{ 
              type: "spring", 
              damping: 25, 
              stiffness: 200,
              opacity: { duration: 0.2 }
            }}
            className="fixed left-0 top-0 h-full w-64 z-50 lg:hidden bg-[#0a101e]/95 backdrop-blur-xl border-r border-gray-800/50"
            style={{ 
              boxShadow: '0 0 20px rgba(0, 0, 0, 0.5)',
              borderRight: '1px solid rgba(59, 130, 246, 0.3)'
            }}
          >
            <UserSidebar 
              open={sidebarOpen} 
              setOpen={setSidebarOpen} 
              onNavigationClick={handleNavigationClick}
            />
          </motion.div>
        )}
      </AnimatePresence>

      {/* Desktop Sidebar */}
      <div className="hidden lg:block">
        <UserSidebar 
          open={true} 
          setOpen={() => {}} 
          onNavigationClick={() => {}}
        />
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col lg:ml-64">
        <MorphingBackground />
        <FloatingParticles />
        
        {/* Header */}
        <motion.header
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8 }}
          className="relative z-50 px-2 lg:px-4 py-6 border-b border-gray-800/50 backdrop-blur-xl"
        >
          <nav className="flex items-center justify-between">
            <div className="flex items-center space-x-4 lg:space-x-6">
              {/* Mobile menu button */}
              <motion.button
                ref={sidebarButtonRef}
                className={`lg:hidden mr-2 p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 ${
                  sidebarOpen 
                    ? 'bg-blue-600/20 text-blue-400 ring-2 ring-blue-500/50' 
                    : 'hover:bg-gray-800/50'
                }`}
                onClick={toggleSidebar}
                aria-label="Open sidebar menu"
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <motion.div
                  animate={sidebarOpen ? { rotate: 90 } : { rotate: 0 }}
                  transition={{ duration: 0.2 }}
                >
                  <Menu className="w-6 h-6" />
                </motion.div>
              </motion.button>
              
              <Link href="/">
                <motion.div
                  className="flex items-center space-x-3"
                  whileHover={{ scale: 1.02 }}
                  transition={{ type: "spring", stiffness: 400, damping: 25 }}
                >
                  <VaultLogo width={180} height={50} className="h-12 w-auto" />
                </motion.div>
              </Link>
              
              <div className="hidden md:flex items-center space-x-6">
                <Link 
                  href="/" 
                  className="flex items-center space-x-2 text-gray-400 hover:text-white transition-colors duration-200"
                  onClick={handleNavigationClick}
                >
                  <ArrowLeft className="w-4 h-4" />
                  <span className="text-sm font-medium">Back to Home</span>
                </Link>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              <WalletConnection />
            </div>
          </nav>
        </motion.header>

        {/* Main Content */}
        <main className="relative z-10 flex-1 overflow-y-auto md:ml-[-250px]">
          <div className="px-2 sm:px-4 lg:px-2 xl:px-4 py-8">
            {/* Welcome Section */}
            <motion.div 
              variants={containerVariants} 
              initial="hidden" 
              animate="visible" 
              className="mb-8"
            >
              <DashboardWelcomeSection 
                getDisplayName={getDisplayName}
                setShowDepositModal={setShowDepositModal}
                setShowWithdrawModal={setShowWithdrawModal}
                itemVariants={itemVariants}
              />

              {/* Portfolio Overview Cards */}
              <PortfolioOverviewCards 
                userData={userData}
                showBalance={showBalance}
                setShowBalance={setShowBalance}
                itemVariants={itemVariants}
              />
            </motion.div>

            {/* Portfolio Chart and Quick Actions */}
            <motion.div
              variants={containerVariants}
              initial="hidden"
              animate="visible"
              className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8"
            >
              <motion.div variants={itemVariants} className="lg:col-span-3">
                <PortfolioChart />
              </motion.div>
              
              <QuickActionsCard 
                setShowDepositModal={setShowDepositModal}
                setShowWithdrawModal={setShowWithdrawModal}
                itemVariants={itemVariants}
              />
            </motion.div>

            {/* Staking Positions and Transaction History */}
            <motion.div
              variants={containerVariants}
              initial="hidden"
              animate="visible"
              className="grid grid-cols-1 lg:grid-cols-3 gap-6"
            >
              <motion.div variants={itemVariants} className="lg:col-span-1">
                <StakingPositions />
              </motion.div>
              <motion.div variants={itemVariants} className="lg:col-span-2">
                <TransactionHistory />
              </motion.div>
            </motion.div>
          </div>
        </main>

        {/* Modals */}
        <AnimatePresence>
          {showDepositModal && <DepositModal onClose={() => setShowDepositModal(false)} />}
          {showWithdrawModal && <WithdrawModal onClose={() => setShowWithdrawModal(false)} />}
        </AnimatePresence>

        {/* Footer */}
        <motion.footer
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8 }}
          className="border-t border-gray-800/50 px-6 py-8 relative z-10 backdrop-blur-xl bg-black/80"
        >
          <div className="max-w-7xl mx-auto">
            <div className="flex flex-col md:flex-row justify-between items-center">
              <motion.div
                className="flex items-center space-x-3 mb-4 md:mb-0"
                whileHover={{ scale: 1.05 }}
                transition={{ type: 'spring', stiffness: 400 }}
              >
                <VaultLogo
                  width={160}
                  height={40}
                  className="h-10 w-auto opacity-80 hover:opacity-100 transition-opacity duration-300"
                />
              </motion.div>
              <motion.div
                className="flex items-center space-x-6 text-sm text-gray-400"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.8 }}
              >
                <Link
                  href="/plans"
                  className="hover:text-white transition-colors duration-200"
                  onClick={handleNavigationClick}
                >
                  Staking Plans
                </Link>
                <Link
                  href="/roadmap"
                  className="hover:text-white transition-colors duration-200"
                  onClick={handleNavigationClick}
                >
                  Roadmap
                </Link>
                <span className="text-gray-600">|</span>
                <span className="text-gray-500">
                  © {new Date().getFullYear()} Vault. All rights reserved.
                </span>
              </motion.div>
            </div>
          </div>
        </motion.footer>
      </div>
    </div>
  )
}