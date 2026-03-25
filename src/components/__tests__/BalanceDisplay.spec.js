import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BalanceDisplay from '../BalanceDisplay.vue'

describe('BalanceDisplay', () => {
    it('renders policy name and balance', () => {
        const wrapper = mount(BalanceDisplay, {
            props: {
                policyName: 'Vacation',
                balance: 80,
                policyType: 'fixed',
            },
        })

        expect(wrapper.text()).toContain('Vacation')
        expect(wrapper.text()).toContain('80')
        expect(wrapper.text()).toContain('hours available')
    })

    it('shows accrual info for accrual-type policies', () => {
        const wrapper = mount(BalanceDisplay, {
            props: {
                policyName: 'PTO',
                balance: 120,
                policyType: 'accrual',
                accrualRate: 10,
                accrualPeriod: 'monthly',
            },
        })

        expect(wrapper.text()).toContain('Accrues 10 hours monthly')
    })

    it('does not show accrual info for non-accrual policies', () => {
        const wrapper = mount(BalanceDisplay, {
            props: {
                policyName: 'Vacation',
                balance: 80,
                policyType: 'unlimited',
            },
        })

        expect(wrapper.text()).not.toContain('Accrues')
    })

    it('displays zero balance correctly', () => {
        const wrapper = mount(BalanceDisplay, {
            props: {
                policyName: 'Sick Leave',
                balance: 0,
                policyType: 'fixed',
            },
        })

        expect(wrapper.text()).toContain('0')
    })
})
