"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { AnimatedCounter } from "@/components/animated-counter"
import { Wallet, Shield, TrendingUp, Clock, Zap, Eye, EyeOff } from "lucide-react"

interface PortfolioOverviewCardsProps {
  userData: {
    totalBalance: number
    stakedAmount: number
    availableBalance: number
    totalRewards: number
    portfolioChange: number
    apy: number
  }
  showBalance: boolean
  setShowBalance: (show: boolean) => void
  itemVariants: any
}

export function PortfolioOverviewCards({ 
  userData, 
  showBalance, 
  setShowBalance, 
  itemVariants 
}: PortfolioOverviewCardsProps) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6">
      <motion.div variants={itemVariants}>
        <Card className="bg-gradient-to-br from-blue-900/20 to-cyan-900/20 border-blue-500/30 backdrop-blur-sm p-6 lg:p-8 hover:border-blue-400/50 transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/10 h-full">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center space-x-4">
              <div className="w-14 h-14 bg-gradient-to-r from-blue-500 to-cyan-400 rounded-xl flex items-center justify-center shadow-lg">
                <Wallet className="w-7 h-7 text-white" />
              </div>
              <div>
                <p className="text-sm text-gray-400 font-medium">Total Portfolio</p>
                <div className="flex items-center space-x-2">
                  <span className="text-2xl lg:text-3xl xl:text-4xl font-bold text-white">
                    {showBalance ? (
                      <AnimatedCounter end={userData.totalBalance} prefix="$" decimals={2} duration={1.5} />
                    ) : (
                      "••••••"
                    )}
                  </span>
                  <button
                    onClick={() => setShowBalance(!showBalance)}
                    className="text-gray-400 hover:text-white transition-colors p-1 rounded"
                  >
                    {showBalance ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <TrendingUp className="w-4 h-4 text-green-400" />
            <span className="text-green-400 text-sm font-medium">
              +<AnimatedCounter end={userData.portfolioChange} suffix="%" decimals={1} duration={1.5} />
            </span>
            <span className="text-gray-400 text-sm">this month</span>
          </div>
        </Card>
      </motion.div>

      <motion.div variants={itemVariants}>
        <Card className="bg-gradient-to-br from-purple-900/20 to-purple-800/20 border-purple-500/30 backdrop-blur-sm p-6 lg:p-8 hover:border-purple-400/50 transition-all duration-300 hover:shadow-lg hover:shadow-purple-500/10 h-full">
          <div className="flex items-center space-x-4 mb-6">
            <div className="w-14 h-14 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
              <Shield className="w-7 h-7 text-white" />
            </div>
            <div>
              <p className="text-sm text-gray-400 font-medium">Staked Amount</p>
              <div className="text-2xl lg:text-3xl xl:text-4xl font-bold text-white">
                {showBalance ? (
                  <AnimatedCounter end={userData.stakedAmount} prefix="$" decimals={2} duration={1.5} />
                ) : (
                  "••••••"
                )}
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <Zap className="w-4 h-4 text-blue-400" />
            <span className="text-blue-400 text-sm font-medium">
              <AnimatedCounter end={userData.apy} suffix="% APY" decimals={1} duration={1.5} />
            </span>
          </div>
        </Card>
      </motion.div>

      <motion.div variants={itemVariants}>
        <Card className="bg-gradient-to-br from-green-900/20 to-green-800/20 border-green-500/30 backdrop-blur-sm p-6 lg:p-8 hover:border-green-400/50 transition-all duration-300 hover:shadow-lg hover:shadow-green-500/10 h-full">
          <div className="flex items-center space-x-4 mb-6">
            <div className="w-14 h-14 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
              <TrendingUp className="w-7 h-7 text-white" />
            </div>
            <div>
              <p className="text-sm text-gray-400 font-medium">Total Rewards</p>
              <div className="text-2xl lg:text-3xl xl:text-4xl font-bold text-white">
                {showBalance ? (
                  <AnimatedCounter end={userData.totalRewards} prefix="$" decimals={2} duration={1.5} />
                ) : (
                  "••••••"
                )}
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <Clock className="w-4 h-4 text-green-400" />
            <span className="text-green-400 text-sm font-medium">Daily earnings</span>
          </div>
        </Card>
      </motion.div>

      <motion.div variants={itemVariants}>
        <Card className="bg-gradient-to-br from-cyan-900/20 to-cyan-800/20 border-cyan-500/30 backdrop-blur-sm p-6 lg:p-8 hover:border-cyan-400/50 transition-all duration-300 hover:shadow-lg hover:shadow-cyan-500/10 h-full">
          <div className="flex items-center space-x-4 mb-6">
            <div className="w-14 h-14 bg-gradient-to-r from-cyan-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
              <Wallet className="w-7 h-7 text-white" />
            </div>
            <div>
              <p className="text-sm text-gray-400 font-medium">Available Balance</p>
              <div className="text-2xl lg:text-3xl xl:text-4xl font-bold text-white">
                {showBalance ? (
                  <AnimatedCounter end={userData.availableBalance} prefix="$" decimals={2} duration={1.5} />
                ) : (
                  "••••••"
                )}
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <div className="w-2 h-2 bg-green-400 rounded-full animate-pulse" />
            <span className="text-gray-400 text-sm">Ready to stake</span>
          </div>
        </Card>
      </motion.div>
    </div>
  )
} 