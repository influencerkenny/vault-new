"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { ArrowUpRight, ArrowDownLeft, Plus, TrendingUp } from "lucide-react"

export function TransactionHistory() {
  const transactions = [
    {
      id: 1,
      type: "stake",
      amount: 850.5,
      status: "completed",
      timestamp: "2 hours ago",
      hash: "5KJp...9mNx",
      icon: Plus,
      color: "text-blue-400",
    },
    {
      id: 2,
      type: "reward",
      amount: 12.34,
      status: "completed",
      timestamp: "1 day ago",
      hash: "8Hm2...4kLp",
      icon: TrendingUp,
      color: "text-green-400",
    },
    {
      id: 3,
      type: "deposit",
      amount: 1000.0,
      status: "completed",
      timestamp: "3 days ago",
      hash: "2Nx8...7qRt",
      icon: ArrowDownLeft,
      color: "text-cyan-400",
    },
    {
      id: 4,
      type: "withdraw",
      amount: 250.0,
      status: "completed",
      timestamp: "1 week ago",
      hash: "9Kp4...2mVx",
      icon: ArrowUpRight,
      color: "text-red-400",
    },
  ]

  const getStatusColor = (status: string) => {
    switch (status) {
      case "completed":
        return "text-green-400 border-green-400/30 bg-green-400/10"
      case "pending":
        return "text-yellow-400 border-yellow-400/30 bg-yellow-400/10"
      case "failed":
        return "text-red-400 border-red-400/30 bg-red-400/10"
      default:
        return "text-gray-400 border-gray-400/30 bg-gray-400/10"
    }
  }

  const getTypeLabel = (type: string) => {
    switch (type) {
      case "stake":
        return "Stake"
      case "reward":
        return "Reward"
      case "deposit":
        return "Deposit"
      case "withdraw":
        return "Withdraw"
      default:
        return type
    }
  }

  return (
    <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-6">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-xl font-semibold">Recent Transactions</h3>
        <Badge variant="outline" className="text-blue-400 border-blue-400/30">
          View All
        </Badge>
      </div>

      <div className="space-y-3">
        {transactions.map((transaction, index) => (
          <motion.div
            key={transaction.id}
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.6, delay: index * 0.1 }}
            className="flex items-center justify-between p-3 bg-gray-800/30 rounded-lg border border-gray-700/50 hover:border-blue-500/30 transition-all duration-300"
          >
            <div className="flex items-center space-x-3">
              <div className={`w-8 h-8 bg-gray-700 rounded-lg flex items-center justify-center ${transaction.color}`}>
                <transaction.icon className="w-4 h-4" />
              </div>
              <div>
                <div className="flex items-center space-x-2">
                  <span className="font-medium text-white text-sm">{getTypeLabel(transaction.type)}</span>
                  <Badge variant="outline" className={`text-xs ${getStatusColor(transaction.status)}`}>
                    {transaction.status}
                  </Badge>
                </div>
                <div className="flex items-center space-x-2 text-xs text-gray-400">
                  <span>{transaction.timestamp}</span>
                  <span>•</span>
                  <span className="font-mono">{transaction.hash}</span>
                </div>
              </div>
            </div>
            <div className="text-right">
              <div className={`font-medium ${transaction.type === "withdraw" ? "text-red-400" : "text-green-400"}`}>
                {transaction.type === "withdraw" ? "-" : "+"}${transaction.amount.toFixed(2)}
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      <motion.div
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6, delay: 0.5 }}
        className="mt-4 text-center"
      >
        <button className="text-blue-400 hover:text-blue-300 text-sm transition-colors">
          View transaction history
        </button>
      </motion.div>
    </Card>
  )
}
