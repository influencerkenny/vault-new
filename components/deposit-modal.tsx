"use client"

import { useState } from "react"
import { motion } from "framer-motion"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { X, ArrowDownLeft, Copy, ExternalLink } from "lucide-react"

interface DepositModalProps {
  onClose: () => void
}

export function DepositModal({ onClose }: DepositModalProps) {
  const [amount, setAmount] = useState("")
  const [isLoading, setIsLoading] = useState(false)
  const depositAddress = "7xKXtg2CW87d97TXJSDpbD5jBkheTqA83TZRuJosgAsU"

  const handleDeposit = async () => {
    if (!amount || Number.parseFloat(amount) <= 0) return

    setIsLoading(true)
    // Simulate deposit process
    await new Promise((resolve) => setTimeout(resolve, 2000))
    setIsLoading(false)
    onClose()
  }

  const copyAddress = () => {
    navigator.clipboard.writeText(depositAddress)
  }

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      onClick={onClose}
    >
      <motion.div
        initial={{ scale: 0.9, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        exit={{ scale: 0.9, opacity: 0 }}
        transition={{ type: "spring", stiffness: 300, damping: 25 }}
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-md"
      >
        <Card className="bg-gray-900/95 border-gray-800/50 backdrop-blur-sm p-6">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center space-x-3">
              <div className="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                <ArrowDownLeft className="w-5 h-5 text-white" />
              </div>
              <div>
                <h2 className="text-xl font-semibold text-white">Deposit SOL</h2>
                <p className="text-sm text-gray-400">Add funds to your wallet</p>
              </div>
            </div>
            <Button onClick={onClose} variant="ghost" size="sm" className="text-gray-400 hover:text-white">
              <X className="w-5 h-5" />
            </Button>
          </div>

          <div className="space-y-6">
            {/* Deposit Address */}
            <div className="space-y-2">
              <Label className="text-sm font-medium text-gray-300">Deposit Address</Label>
              <div className="flex items-center space-x-2 p-3 bg-gray-800/50 rounded-lg border border-gray-700">
                <span className="text-sm text-gray-300 font-mono flex-1 truncate">{depositAddress}</span>
                <Button onClick={copyAddress} variant="ghost" size="sm" className="text-gray-400 hover:text-white p-1">
                  <Copy className="w-4 h-4" />
                </Button>
                <Button variant="ghost" size="sm" className="text-gray-400 hover:text-white p-1">
                  <ExternalLink className="w-4 h-4" />
                </Button>
              </div>
              <p className="text-xs text-gray-500">Only send SOL to this address. Other tokens will be lost.</p>
            </div>

            {/* Amount Input */}
            <div className="space-y-2">
              <Label htmlFor="amount" className="text-sm font-medium text-gray-300">
                Amount (SOL)
              </Label>
              <div className="relative">
                <Input
                  id="amount"
                  type="number"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  className="bg-gray-800/50 border-gray-700 text-white placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500/20"
                  placeholder="0.00"
                  step="0.01"
                  min="0"
                />
                <div className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">SOL</div>
              </div>
            </div>

            {/* Quick Amount Buttons */}
            <div className="grid grid-cols-4 gap-2">
              {[1, 5, 10, 25].map((value) => (
                <Button
                  key={value}
                  onClick={() => setAmount(value.toString())}
                  variant="outline"
                  size="sm"
                  className="border-gray-700 text-gray-300 hover:bg-gray-800/50 hover:border-gray-600 hover:text-white"
                >
                  {value}
                </Button>
              ))}
            </div>

            {/* Deposit Button */}
            <Button
              onClick={handleDeposit}
              disabled={!amount || Number.parseFloat(amount) <= 0 || isLoading}
              className="w-full bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white py-3 rounded-lg font-medium transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isLoading ? (
                <div className="flex items-center justify-center space-x-2">
                  <div className="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin" />
                  <span>Processing...</span>
                </div>
              ) : (
                `Deposit ${amount || "0"} SOL`
              )}
            </Button>

            <div className="text-xs text-gray-500 text-center">
              Deposits typically take 1-2 minutes to confirm on the Solana network
            </div>
          </div>
        </Card>
      </motion.div>
    </motion.div>
  )
}
