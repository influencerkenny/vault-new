"use client"

import { motion } from "framer-motion"

export function RotatingDataCube() {
  return (
    <div className="absolute inset-0 flex items-center justify-center">
      <motion.div
        className="relative w-32 h-32"
        animate={{
          rotateX: [0, 360],
          rotateY: [0, 360],
        }}
        transition={{
          duration: 20,
          repeat: Number.POSITIVE_INFINITY,
          ease: "linear",
        }}
        style={{ transformStyle: "preserve-3d" }}
      >
        {/* Cube faces */}
        {[
          { transform: "rotateY(0deg) translateZ(64px)", bg: "from-blue-500/20 to-blue-600/20" },
          { transform: "rotateY(90deg) translateZ(64px)", bg: "from-cyan-400/20 to-cyan-500/20" },
          { transform: "rotateY(180deg) translateZ(64px)", bg: "from-blue-600/20 to-blue-700/20" },
          { transform: "rotateY(-90deg) translateZ(64px)", bg: "from-cyan-500/20 to-blue-500/20" },
          { transform: "rotateX(90deg) translateZ(64px)", bg: "from-blue-400/20 to-cyan-400/20" },
          { transform: "rotateX(-90deg) translateZ(64px)", bg: "from-cyan-600/20 to-blue-600/20" },
        ].map((face, index) => (
          <motion.div
            key={index}
            className={`absolute w-32 h-32 bg-gradient-to-br ${face.bg} border border-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center`}
            style={{ transform: face.transform }}
            animate={{
              opacity: [0.6, 1, 0.6],
            }}
            transition={{
              duration: 3,
              repeat: Number.POSITIVE_INFINITY,
              delay: index * 0.2,
            }}
          >
            <div className="text-white/80 text-xs font-mono">
              {["SOL", "ETH", "BTC", "USDC", "AVAX", "MATIC"][index]}
            </div>
          </motion.div>
        ))}
      </motion.div>
    </div>
  )
}
