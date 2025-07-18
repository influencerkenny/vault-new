"use client"

import { motion } from "framer-motion"

export function HolographicDisplay() {
  return (
    <div className="absolute bottom-4 left-4 w-40 h-24 perspective-1000">
      <motion.div
        className="relative w-full h-full bg-gradient-to-br from-cyan-500/10 to-blue-600/10 rounded-lg border border-cyan-400/30 backdrop-blur-sm"
        animate={{
          rotateX: [0, 5, 0],
          rotateY: [0, -5, 0],
        }}
        transition={{
          duration: 6,
          repeat: Number.POSITIVE_INFINITY,
          ease: "easeInOut",
        }}
      >
        {/* Holographic scan lines */}
        <motion.div
          className="absolute inset-0 bg-gradient-to-b from-transparent via-cyan-400/20 to-transparent"
          animate={{
            y: ["-100%", "100%"],
          }}
          transition={{
            duration: 2,
            repeat: Number.POSITIVE_INFINITY,
            ease: "linear",
          }}
        />

        <div className="p-3 relative z-10">
          <div className="text-xs text-cyan-400 mb-1">Network Status</div>
          <div className="flex items-center space-x-2 mb-2">
            <motion.div
              className="w-2 h-2 bg-green-400 rounded-full"
              animate={{ opacity: [1, 0.5, 1] }}
              transition={{ duration: 1.5, repeat: Number.POSITIVE_INFINITY }}
            />
            <span className="text-xs text-white">Online</span>
          </div>
          <div className="text-xs text-gray-400">
            <div>Validators: 1,847</div>
            <div>Block Height: 234,567,890</div>
          </div>
        </div>

        {/* Holographic glow */}
        <motion.div
          className="absolute inset-0 bg-cyan-400/5 rounded-lg blur-sm"
          animate={{
            opacity: [0.3, 0.6, 0.3],
            scale: [1, 1.02, 1],
          }}
          transition={{
            duration: 3,
            repeat: Number.POSITIVE_INFINITY,
            ease: "easeInOut",
          }}
        />
      </motion.div>
    </div>
  )
}
