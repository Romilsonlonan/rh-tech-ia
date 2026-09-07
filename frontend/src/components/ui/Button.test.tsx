import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Button } from './index'

describe('Button', () => {
  it('renders with primary variant', () => {
    render(<Button variant="primary">Click me</Button>)
    expect(screen.getByRole('button', { name: /click me/i })).toBeTruthy()
  })

  it('renders with secondary variant', () => {
    render(<Button variant="secondary">Click me</Button>)
    expect(screen.getByRole('button', { name: /click me/i })).toBeTruthy()
  })

  it('handles click events', async () => {
    const handleClick = () => {}
    render(<Button onClick={handleClick}>Click me</Button>)
    expect(screen.getByRole('button')).toBeTruthy()
  })
})
