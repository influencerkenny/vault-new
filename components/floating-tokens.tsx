"use client"

import { motion } from "framer-motion"
import { useEffect, useState } from "react"

interface Token {
  id: number
  symbol: string
  x: number
  y: number
  size: number
  color: string
  duration: number
  delay: number
}

export function FloatingTokens() {
  const [tokens, setTokens] = useState<Token[]>([])

  useEffect(() => {
    const tokenSymbols = ["SOL", "USDC", "ETH", "BTC", "AVAX", "MATIC"]
    const colors = ["#9945FF", "#2775CA", "#627EEA", "#F7931A", "#E84142", "#8247E5"]

    const newTokens: Token[] = tokenSymbols.map((symbol, index) => ({
      id: index,
      symbol,
      x: Math.random() * 80 + 10,
      y: Math.random() * 80 + 10,
      size: Math.random() * 20 + 30,
      color: colors[index],
      duration: Math.random() * 8 + 12,
      delay: Math.random() * 3,
    }))

    setTokens(newTokens)
  }, [])

  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none">
      {tokens.map((token) => (
        <motion.div
          key={token.id}
          className="absolute rounded-full flex items-center justify-center text-white font-bold text-xs border border-white/20 backdrop-blur-sm"
          style={{
            left: `${token.x}%`,
            top: `${token.y}%`,
            width: token.size,
            height: token.size,
            backgroundColor: `${token.color}20`,
            borderColor: `${token.color}40`,
          }}
          animate={{
            y: [0, -30, 0],
            x: [0, Math.random() * 20 - 10, 0],
            rotate: [0, 360],
            scale: [1, 1.2, 1],
          }}
          transition={{
            duration: token.duration,
            repeat: Number.POSITIVE_INFINITY,
            delay: token.delay,
            ease: "easeInOut",
          }}
        >
          {token.symbol}
        </motion.div>
      ))}
    </div>
  )
}
