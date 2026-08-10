const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

export async function api(path, options = {}) {
    const isFormData = options.body instanceof FormData
    const response = await fetch(path, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers ?? {}),
        },
        ...options,
        body: options.body ? (isFormData ? options.body : JSON.stringify(options.body)) : undefined,
    })

    if (response.status === 204) {
        return null
    }

    const data = await response.json().catch(() => ({}))

    if (!response.ok) {
        const message = data.message || Object.values(data.errors ?? {})?.flat()?.[0] || 'Có lỗi xảy ra. Vui lòng thử lại.'
        throw new Error(message)
    }

    return data
}
