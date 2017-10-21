import heapq, time


def merge(*iterables):
    """
        Sort iterables of tuples in descending mode.
    """

    h = []
    for it in map(iter, iterables):
        try:
            next = it.next
            v = next()
            h.append([(-v[0], v[1]), next])
        except StopIteration:
            pass
    heapq.heapify(h)

    while True:
        try:
            while True:
                v, next = s = h[0]
                yield -v[0], v[1]
                v = next()
                s[0] = -v[0], v[1]
                heapq._siftup(h, 0)
        except StopIteration:
            heapq.heappop(h)
        except IndexError:
            return


def reverse_insort(a, x, lo=0, hi=None):
    """Insert item x in list a, and keep it reverse-sorted assuming a
    is reverse-sorted.

    If x is already in a, insert it to the right of the rightmost x.

    Optional args lo (default 0) and hi (default len(a)) bound the
    slice of a to be searched.
    """
    if lo < 0:
        raise ValueError('lo must be non-negative')
    if hi is None:
        hi = len(a)
    while lo < hi:
        mid = (lo+hi)//2
        if x > a[mid]: hi = mid
        else: lo = mid+1
    a.insert(lo, x)


def get_timestamp():
    return int(time.time())
