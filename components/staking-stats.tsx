"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { TrendingUp, Wallet, Clock, DollarSign } from "lucide-react"
import { AnimatedCounter } from "./animated-counter"

export function StakingStats() {
  const stats = [
    {
      icon: Wallet,
      label: "Total Staked",
      value: 1250.75,
      suffix: " SOL",
      color: "text-blue-400",
      bgColor: "from-blue-500 to-blue-600",
    },
    {
      icon: DollarSign,
      label: "Total Rewards",
      value: 156.32,
      suffix: " SOL",
      color: "text-green-400",
      bgColor: "from-green-500 to-green-600",
    },
    {
      icon: TrendingUp,
      label: "Current APY",
      value: 12.5,
      suffix: "%",
      color: "text-cyan-400",
      bgColor: "from-cyan-500 to-cyan-600",
    },
    {
      icon: Clock,
      label: "Days Staking",
      value: 127,
      suffix: " days",
      color: "text-purple-400",
      bgColor: "from-purple-500 to-purple-600",
    },
  ]

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {stats.map((stat, index) => (
        <motion.div
          key={stat.label}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: index * 0.1 }}
        >
          <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-6 hover:border-blue-500/30 transition-all duration-300 group">
            <div className="flex items-center space-x-4">
              <div
                className={`w-12 h-12 bg-gradient-to-r ${stat.bgColor} rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300`}
              >
                <stat.icon className="w-6 h-6 text-white" />
              </div>
              <div>
                <p className="text-sm text-gray-400 mb-1">{stat.label}</p>
                <div className={`text-2xl font-bold ${stat.color}`}>
                  <AnimatedCounter
                    end={stat.value}
                    suffix={stat.suffix}
                    decimals={stat.suffix.includes("%") || stat.suffix.includes("SOL") ? 2 : 0}
                    duration={2}
                  />
                </div>
              </div>
            </div>
          </Card>
        </motion.div>
      ))}
    </div>
  )
}
