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


def get_timestamp():
    return int(time.time())
