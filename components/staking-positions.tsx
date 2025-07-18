"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Star, Zap, MoreHorizontal } from "lucide-react"

export function StakingPositions() {
  const positions = [
    {
      id: 1,
      plan: "Prime Plan",
      icon: Zap,
      amount: 850.5,
      apy: 1.8,
      dailyRewards: 1.53,
      status: "Active",
      daysLeft: 18,
      color: "from-blue-500 to-cyan-400",
    },
    {
      id: 2,
      plan: "Core Plan",
      icon: Star,
      amount: 400.25,
      apy: 1.2,
      dailyRewards: 0.48,
      status: "Active",
      daysLeft: 8,
      color: "from-gray-500 to-gray-600",
    },
  ]

  return (
    <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-6">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-xl font-semibold">Active Staking Positions</h3>
        <Badge variant="outline" className="text-green-400 border-green-400/30">
          {positions.length} Active
        </Badge>
      </div>

      <div className="space-y-4">
        {positions.map((position, index) => (
          <motion.div
            key={position.id}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: index * 0.1 }}
            className="p-4 bg-gray-800/30 rounded-lg border border-gray-700/50 hover:border-blue-500/30 transition-all duration-300"
          >
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center space-x-3">
                <div
                  className={`w-8 h-8 bg-gradient-to-r ${position.color} rounded-lg flex items-center justify-center`}
                >
                  <position.icon className="w-4 h-4 text-white" />
                </div>
                <div>
                  <h4 className="font-medium text-white">{position.plan}</h4>
                  <p className="text-sm text-gray-400">{position.daysLeft} days remaining</p>
                </div>
              </div>
              <Button variant="ghost" size="sm" className="text-gray-400 hover:text-white">
                <MoreHorizontal className="w-4 h-4" />
              </Button>
            </div>

            <div className="grid grid-cols-3 gap-4 mb-3">
              <div>
                <p className="text-xs text-gray-400">Staked Amount</p>
                <p className="text-sm font-medium text-white">${position.amount.toFixed(2)}</p>
              </div>
              <div>
                <p className="text-xs text-gray-400">Daily Rewards</p>
                <p className="text-sm font-medium text-green-400">${position.dailyRewards.toFixed(2)}</p>
              </div>
              <div>
                <p className="text-xs text-gray-400">APY</p>
                <p className="text-sm font-medium text-blue-400">{position.apy}%</p>
              </div>
            </div>

            <div className="flex items-center justify-between">
              <Badge variant="outline" className="text-green-400 border-green-400/30 bg-green-400/10">
                {position.status}
              </Badge>
              <div className="flex space-x-2">
                <Button size="sm" variant="outline" className="text-xs border-gray-600 text-gray-300 hover:bg-gray-700">
                  Unstake
                </Button>
                <Button size="sm" className="text-xs bg-blue-600 hover:bg-blue-700">
                  Add More
                </Button>
              </div>
            </div>
          </motion.div>
        ))}

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.3 }}
          className="p-4 border-2 border-dashed border-gray-700 rounded-lg text-center"
        >
          <p className="text-gray-400 text-sm mb-2">Start a new staking position</p>
          <Button
            size="sm"
            className="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600"
          >
            Browse Plans
          </Button>
        </motion.div>
      </div>
    </Card>
  )
}
