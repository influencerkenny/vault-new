"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Settings, Eye, EyeOff } from "lucide-react"

interface AccessibilityControlsProps {
  onMotionToggle?: (enabled: boolean) => void
}

export function AccessibilityControls({ onMotionToggle }: AccessibilityControlsProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [motionEnabled, setMotionEnabled] = useState(true)
  const [highContrast, setHighContrast] = useState(false)

  useEffect(() => {
    // Check for user's motion preference
    const mediaQuery = window.matchMedia("(prefers-reduced-motion: reduce)")
    setMotionEnabled(!mediaQuery.matches)

    const handleChange = (e: MediaQueryListEvent) => {
      setMotionEnabled(!e.matches)
      onMotionToggle?.(!e.matches)
    }

    mediaQuery.addEventListener("change", handleChange)
    return () => mediaQuery.removeEventListener("change", handleChange)
  }, [onMotionToggle])

  const toggleMotion = () => {
    const newState = !motionEnabled
    setMotionEnabled(newState)
    onMotionToggle?.(newState)

    // Store preference
    localStorage.setItem("motion-preference", newState.toString())
  }

  const toggleHighContrast = () => {
    setHighContrast(!highContrast)
    document.documentElement.classList.toggle("high-contrast", !highContrast)
  }

  if (!isOpen) {
    return (
      <Button
        onClick={() => setIsOpen(true)}
        className="fixed bottom-4 right-4 bg-slate-800/90 hover:bg-slate-700/90 text-white p-3 rounded-full shadow-lg z-50"
        size="sm"
      >
        <Settings className="w-5 h-5" />
        <span className="sr-only">Accessibility Settings</span>
      </Button>
    )
  }

  return (
    <Card className="fixed bottom-4 right-4 bg-slate-800/95 border-slate-700 p-4 z-50 min-w-[280px]">
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-white font-medium">Accessibility</h3>
          <Button onClick={() => setIsOpen(false)} variant="ghost" size="sm" className="text-gray-400 hover:text-white">
            ×
          </Button>
        </div>

        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <label className="text-sm text-gray-300">Animations</label>
            <Button
              onClick={toggleMotion}
              variant="ghost"
              size="sm"
              className={`${motionEnabled ? "text-green-400" : "text-red-400"}`}
            >
              {motionEnabled ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
            </Button>
          </div>

          <div className="flex items-center justify-between">
            <label className="text-sm text-gray-300">High Contrast</label>
            <Button
              onClick={toggleHighContrast}
              variant="ghost"
              size="sm"
              className={`${highContrast ? "text-green-400" : "text-gray-400"}`}
            >
              {highContrast ? "ON" : "OFF"}
            </Button>
          </div>
        </div>

        <div className="text-xs text-gray-500 pt-2 border-t border-slate-700">Settings are saved automatically</div>
      </div>
    </Card>
  )
}
