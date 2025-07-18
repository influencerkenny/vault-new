"use client"

import { useState } from "react"
import { motion } from "framer-motion"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Wallet, Copy, ExternalLink } from "lucide-react"

export function WalletConnection() {
  const [isConnected, setIsConnected] = useState(false)
  const [walletAddress] = useState("7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU")

  const connectWallet = () => {
    setIsConnected(true)
  }

  const disconnectWallet = () => {
    setIsConnected(false)
  }

  const copyAddress = () => {
    navigator.clipboard.writeText(walletAddress)
  }

  if (!isConnected) {
    return (
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6 }}>
        <Button
          onClick={connectWallet}
          className="bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white px-6 py-2 rounded-full font-medium transition-all duration-300 group relative overflow-hidden"
        >
          <motion.div className="absolute inset-0 bg-gradient-to-r from-cyan-400/20 to-blue-400/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
          <Wallet className="w-4 h-4 mr-2 relative z-10" />
          <span className="relative z-10">Connect Wallet</span>
        </Button>
      </motion.div>
    )
  }

  return (
    <motion.div
      initial={{ opacity: 0, scale: 0.95 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={{ duration: 0.4 }}
      className="relative"
    >
      <Card className="bg-gray-900/50 border-gray-800/50 backdrop-blur-sm p-4 min-w-[280px]">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="w-8 h-8 bg-gradient-to-r from-green-400 to-green-500 rounded-full flex items-center justify-center">
              <Wallet className="w-4 h-4 text-white" />
            </div>
            <div>
              <div className="text-sm font-medium text-white">Connected</div>
              <div className="text-xs text-gray-400 font-mono">
                {walletAddress.slice(0, 4)}...{walletAddress.slice(-4)}
              </div>
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <Button onClick={copyAddress} variant="ghost" size="sm" className="text-gray-400 hover:text-white p-1">
              <Copy className="w-4 h-4" />
            </Button>
            <Button onClick={disconnectWallet} variant="ghost" size="sm" className="text-gray-400 hover:text-white p-1">
              <ExternalLink className="w-4 h-4" />
            </Button>
          </div>
        </div>
      </Card>
    </motion.div>
  )
}
