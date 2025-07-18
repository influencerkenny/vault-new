"use client"

import { motion } from "framer-motion"
import { Button } from "@/components/ui/button"
import { Plus, Minus } from "lucide-react"

interface DashboardWelcomeSectionProps {
  getDisplayName: () => string
  setShowDepositModal: (show: boolean) => void
  setShowWithdrawModal: (show: boolean) => void
  itemVariants: any
}

export function DashboardWelcomeSection({ 
  getDisplayName, 
  setShowDepositModal, 
  setShowWithdrawModal, 
  itemVariants 
}: DashboardWelcomeSectionProps) {
  return (
    <motion.div
      variants={itemVariants}
      className="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8"
    >
      <div className="mb-6 lg:mb-0 lg:flex-1">
        <h1 className="text-3xl lg:text-4xl xl:text-5xl font-bold mb-3">
          Welcome back,{" "}
          <span className="bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
            {getDisplayName()}
          </span>
        </h1>
        <p className="text-gray-400 text-lg lg:text-xl">
          Here's your portfolio overview and latest performance metrics
        </p>
      </div>
      
      <div className="flex flex-col sm:flex-row items-stretch sm:items-center space-y-3 sm:space-y-0 sm:space-x-4 lg:ml-8">
        <Button
          onClick={() => setShowDepositModal(true)}
          className="bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white px-6 py-3 rounded-full font-medium transition-all duration-300 shadow-lg hover:shadow-green-500/25"
        >
          <Plus className="w-4 h-4 mr-2" />
          Deposit
        </Button>
        <Button
          onClick={() => setShowWithdrawModal(true)}
          variant="outline"
          className="border-gray-700 text-gray-300 hover:bg-gray-800/50 hover:border-gray-600 hover:text-white px-6 py-3 rounded-full font-medium transition-all duration-300"
        >
          <Minus className="w-4 h-4 mr-2" />
          Withdraw
        </Button>
      </div>
    </motion.div>
  )
} 