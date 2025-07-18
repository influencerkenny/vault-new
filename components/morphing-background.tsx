"use client"

import { motion } from "framer-motion"

export function MorphingBackground() {
  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none">
      {/* Morphing gradient blobs */}
      <motion.div
        className="absolute -top-1/2 -left-1/2 w-full h-full"
        animate={{
          rotate: [0, 360],
          scale: [1, 1.2, 1],
        }}
        transition={{
          duration: 20,
          repeat: Number.POSITIVE_INFINITY,
          ease: "linear",
        }}
      >
        <div className="w-96 h-96 bg-gradient-to-r from-blue-500/10 to-cyan-400/10 rounded-full blur-3xl" />
      </motion.div>

      <motion.div
        className="absolute -bottom-1/2 -right-1/2 w-full h-full"
        animate={{
          rotate: [360, 0],
          scale: [1.2, 1, 1.2],
        }}
        transition={{
          duration: 25,
          repeat: Number.POSITIVE_INFINITY,
          ease: "linear",
        }}
      >
        <div className="w-96 h-96 bg-gradient-to-r from-purple-500/10 to-blue-400/10 rounded-full blur-3xl" />
      </motion.div>

      <motion.div
        className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"
        animate={{
          scale: [1, 1.5, 1],
          opacity: [0.1, 0.3, 0.1],
        }}
        transition={{
          duration: 15,
          repeat: Number.POSITIVE_INFINITY,
          ease: "easeInOut",
        }}
      >
        <div className="w-64 h-64 bg-gradient-to-r from-cyan-400/20 to-blue-500/20 rounded-full blur-2xl" />
      </motion.div>
    </div>
  )
}
