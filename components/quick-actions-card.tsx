"use client"

import { motion } from "framer-motion"
import { Card } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import Link from "next/link"
import { ArrowDownLeft, ArrowUpRight, Plus } from "lucide-react"

interface QuickActionsCardProps {
  setShowDepositModal: (show: boolean) => void
  setShowWithdrawModal: (show: boolean) => void
  itemVariants: any
}

export function QuickActionsCard({ 
  setShowDepositModal, 
  setShowWithdrawModal, 
  itemVariants 
}: QuickActionsCardProps) {
  return (
    <motion.div variants={itemVariants}>
      <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-6 h-full">
        <h3 className="text-xl font-semibold mb-6 text-white">Quick Actions</h3>
        <div className="space-y-4">
          <Button
            onClick={() => setShowDepositModal(true)}
            className="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white py-3 rounded-lg font-medium transition-all duration-300 group shadow-lg hover:shadow-blue-500/25"
          >
            <ArrowDownLeft className="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
            Deposit SOL
          </Button>
          <Button
            onClick={() => setShowWithdrawModal(true)}
            variant="outline"
            className="w-full border-gray-700 text-gray-300 hover:bg-gray-800/50 hover:border-gray-600 hover:text-white py-3 rounded-lg font-medium transition-all duration-300 group"
          >
            <ArrowUpRight className="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
            Withdraw SOL
          </Button>
          <Link href="/plans">
            <Button
              variant="outline"
              className="w-full border-blue-500/30 text-blue-400 hover:bg-blue-500/10 hover:border-blue-400 hover:text-blue-300 py-3 rounded-lg font-medium transition-all duration-300 group"
            >
              <Plus className="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
              New Staking Plan
            </Button>
          </Link>
        </div>
      </Card>
    </motion.div>
  )
} 